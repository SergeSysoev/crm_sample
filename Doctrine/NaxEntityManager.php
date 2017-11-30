<?php

namespace NaxCrmBundle\Doctrine;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\ORM\TransactionRequiredException;
use Symfony\Component\Yaml\Yaml;
use NaxCrmBundle\Entity\BaseEntity;

class NaxEntityManager extends EntityManager
{
    protected $brand = 'Nax';
    protected $parentEm = null;

    protected $filters = null;

    public function __construct(EntityManager $em)
    {
        $this->parentEm = $em;
        parent::__construct($em->getConnection(), $em->getConfiguration(), $em->getEventManager());

        $params = Yaml::parse(file_get_contents(dirname(__FILE__) . '/../../../app/config/parameters.yml'));
        $brand = $params['parameters']['brand'];
        $this->filters = $this->parentEm->getFilters();

        $this->brand = ucfirst($params['parameters']['brand']);
    }
    /**
     * Gets the repository for an entity class.
     *
     * @param string $entityName The name of the entity.
     *
     * @return \Doctrine\ORM\EntityRepository The repository class.
     */
    public function getRepository($entityName)
    {
        $entityName = $this->getEntityName($entityName);
        return parent::getRepository($entityName);
    }

    /**
     * {@inheritDoc}
     */
    public function getUnitOfWork()
    {
        return $this->parentEm->getUnitOfWork();
    }

    /**
     * {@inheritDoc}
     */
    public function getFilters()
    {
        if (null === $this->filters) {
            $this->filters = new FilterCollection($this);
        }

        return $this->filters;
    }

    /**
     * {@inheritDoc}
     */
    public function isFiltersStateClean()
    {
        return null === $this->getFilters() || $this->getFilters()->isClean();
    }

    /**
     * {@inheritDoc}
     */
    public function hasFilters()
    {
        return null !== $this->getFilters();
    }

    /**
     * {@inheritDoc}
     */
    public function getProxyFactory()
    {
        return $this->parentEm->getProxyFactory();
    }

    /**
     * Flushes all changes to objects that have been queued up to now to the database.
     * This effectively synchronizes the in-memory state of managed objects with the
     * database.
     *
     * If an entity is explicitly passed to this method only this entity and
     * the cascade-persist semantics + scheduled inserts/removals are synchronized.
     *
     * @param null|object|array $entity
     *
     * @return void
     *
     * @throws \Doctrine\ORM\OptimisticLockException If a version check on an entity that
     *         makes use of optimistic locking fails.
     */
    public function flush($entity = null)
    {
        $this->_errorIfClosed();

        $this->parentEm->getUnitOfWork()->commit($entity);
    }

    /**
     * Finds an Entity by its identifier.
     *
     * @param string       $entityName  The class name of the entity to find.
     * @param mixed        $id          The identity of the entity to find.
     * @param integer|null $lockMode    One of the \Doctrine\DBAL\LockMode::* constants
     *                                  or NULL if no specific lock mode should be used
     *                                  during the search.
     * @param integer|null $lockVersion The version of the entity to find when using
     *                                  optimistic locking.
     *
     * @return object|null The entity instance or NULL if the entity can not be found.
     *
     * @throws OptimisticLockException
     * @throws ORMInvalidArgumentException
     * @throws TransactionRequiredException
     * @throws ORMException
     */
    public function find($entityName, $id, $lockMode = null, $lockVersion = null)
    {
        $class = $this->getMetadataFactory()->getMetadataFor(ltrim($entityName, '\\'));

        if ( ! is_array($id)) {
            if ($class->isIdentifierComposite) {
                throw ORMInvalidArgumentException::invalidCompositeIdentifier();
            }

            $id = array($class->identifier[0] => $id);
        }

        foreach ($id as $i => $value) {
            if (is_object($value) && $this->getMetadataFactory()->hasMetadataFor(ClassUtils::getClass($value))) {
                $id[$i] = $this->getUnitOfWork()->getSingleIdentifierValue($value);

                if ($id[$i] === null) {
                    throw ORMInvalidArgumentException::invalidIdentifierBindingEntity();
                }
            }
        }

        $sortedId = array();

        foreach ($class->identifier as $identifier) {
            if ( ! isset($id[$identifier])) {
                throw ORMException::missingIdentifierField($class->name, $identifier);
            }

            $sortedId[$identifier] = $id[$identifier];
            unset($id[$identifier]);
        }

        if ($id) {
            throw ORMException::unrecognizedIdentifierFields($class->name, array_keys($id));
        }

        $unitOfWork = $this->getUnitOfWork();

        // Check identity map first
        if (($entity = $unitOfWork->tryGetById($sortedId, $class->rootEntityName)) !== false) {
            if ( ! ($entity instanceof $class->name)) {
                return null;
            }

            switch (true) {
                case LockMode::OPTIMISTIC === $lockMode:
                    $this->lock($entity, $lockMode, $lockVersion);
                    break;

                case LockMode::NONE === $lockMode:
                case LockMode::PESSIMISTIC_READ === $lockMode:
                case LockMode::PESSIMISTIC_WRITE === $lockMode:
                    $persister = $unitOfWork->getEntityPersister($class->name);
                    $persister->refresh($sortedId, $entity, $lockMode);
                    break;
            }

            return $entity; // Hit!
        }

        $persister = $unitOfWork->getEntityPersister($class->name);

        switch (true) {
            case LockMode::OPTIMISTIC === $lockMode:
                if ( ! $class->isVersioned) {
                    throw OptimisticLockException::notVersioned($class->name);
                }

                $entity = $persister->load($sortedId);

                $unitOfWork->lock($entity, $lockMode, $lockVersion);

                return $entity;

            case LockMode::NONE === $lockMode:
            case LockMode::PESSIMISTIC_READ === $lockMode:
            case LockMode::PESSIMISTIC_WRITE === $lockMode:
                if ( ! $this->getConnection()->isTransactionActive()) {
                    throw TransactionRequiredException::transactionRequired();
                }

                return $persister->load($sortedId, null, null, array(), $lockMode);

            default:
                return $persister->loadById($sortedId);
        }
    }
    /**
     * {@inheritDoc}
     */
    public function getReference($entityName, $id)
    {
        $class = $this->getMetadataFactory()->getMetadataFor(ltrim($entityName, '\\'));

        if ( ! is_array($id)) {
            $id = array($class->identifier[0] => $id);
        }

        $sortedId = array();

        foreach ($class->identifier as $identifier) {
            if ( ! isset($id[$identifier])) {
                throw ORMException::missingIdentifierField($class->name, $identifier);
            }

            $sortedId[$identifier] = $id[$identifier];
        }

        // Check identity map first, if its already in there just return it.
        if (($entity = $this->parentEm->getUnitOfWork()->tryGetById($sortedId, $class->rootEntityName)) !== false) {
            return ($entity instanceof $class->name) ? $entity : null;
        }

        if ($class->subClasses) {
            return $this->find($entityName, $sortedId);
        }

        if ( ! is_array($sortedId)) {
            $sortedId = array($class->identifier[0] => $sortedId);
        }

        $entity = $this->getProxyFactory()->getProxy($class->name, $sortedId);

        $this->parentEm->getUnitOfWork()->registerManaged($entity, $sortedId, array());

        return $entity;
    }
    /**
     * {@inheritDoc}
     */
    public function getPartialReference($entityName, $identifier)
    {
        $class = $this->getMetadataFactory()->getMetadataFor(ltrim($entityName, '\\'));

        // Check identity map first, if its already in there just return it.
        if (($entity = $this->parentEm->getUnitOfWork()->tryGetById($identifier, $class->rootEntityName)) !== false) {
            return ($entity instanceof $class->name) ? $entity : null;
        }

        if ( ! is_array($identifier)) {
            $identifier = array($class->identifier[0] => $identifier);
        }

        $entity = $class->newInstance();

        $class->setIdentifierValues($entity, $identifier);

        $this->parentEm->getUnitOfWork()->registerManaged($entity, $identifier, array());
        $this->parentEm->getUnitOfWork()->markReadOnly($entity);

        return $entity;
    }

    /**
     * Clears the EntityManager. All entities that are currently managed
     * by this EntityManager become detached.
     *
     * @param string|null $entityName if given, only entities of this type will get detached
     *
     * @return void
     */
    public function clear($entityName = null)
    {
        $this->parentEm->getUnitOfWork()->clear($entityName);
    }

    /**
     * {@inheritDoc}
     */
    public function resetAll()
    {
        $uow = $this->getUnitOfWork();
        foreach ($uow->getIdentityMap() as $entities) {
            foreach ($entities as $entity) {
                $uow->setOriginalEntityData($entity, []);
            }
        }
        return $this;
    }

    /**
     * Tells the EntityManager to make an instance managed and persistent.
     *
     * The entity will be entered into the database at or before transaction
     * commit or as a result of the flush operation.
     *
     * NOTE: The persist operation always considers entities that are not yet known to
     * this EntityManager as NEW. Do not pass detached entities to the persist operation.
     *
     * @param object $entity The instance to make managed and persistent.
     *
     * @return void
     *
     * @throws ORMInvalidArgumentException
     */
    public function persist($entity)
    {
        if ( ! is_object($entity)) {
            throw ORMInvalidArgumentException::invalidObject('EntityManager#persist()' , $entity);
        }

        $this->_errorIfClosed();

        $this->parentEm->getUnitOfWork()->persist($entity);
    }

    /**
     * Removes an entity instance.
     *
     * A removed entity will be removed from the database at or before transaction commit
     * or as a result of the flush operation.
     *
     * @param object $entity The entity instance to remove.
     *
     * @return void
     *
     * @throws ORMInvalidArgumentException
     */
    public function remove($entity)
    {
        if ( ! is_object($entity)) {
            throw ORMInvalidArgumentException::invalidObject('EntityManager#remove()' , $entity);
        }

        $this->_errorIfClosed();

        $this->parentEm->getUnitOfWork()->remove($entity);
    }

    /**
     * Refreshes the persistent state of an entity from the database,
     * overriding any local changes that have not yet been persisted.
     *
     * @param object $entity The entity to refresh.
     *
     * @return void
     *
     * @throws ORMInvalidArgumentException
     */
    public function refresh($entity)
    {
        if ( ! is_object($entity)) {
            throw ORMInvalidArgumentException::invalidObject('EntityManager#refresh()' , $entity);
        }

        $this->_errorIfClosed();

        $this->parentEm->getUnitOfWork()->refresh($entity);
    }

    /**
     * Detaches an entity from the EntityManager, causing a managed entity to
     * become detached.  Unflushed changes made to the entity if any
     * (including removal of the entity), will not be synchronized to the database.
     * Entities which previously referenced the detached entity will continue to
     * reference it.
     *
     * @param object $entity The entity to detach.
     *
     * @return void
     *
     * @throws ORMInvalidArgumentException
     */
    public function detach($entity)
    {
        if ( ! is_object($entity)) {
            throw ORMInvalidArgumentException::invalidObject('EntityManager#detach()' , $entity);
        }

        $this->parentEm->getUnitOfWork()->detach($entity);
    }

    /**
     * Merges the state of a detached entity into the persistence context
     * of this EntityManager and returns the managed copy of the entity.
     * The entity passed to merge will not become associated/managed with this EntityManager.
     *
     * @param object $entity The detached entity to merge into the persistence context.
     *
     * @return object The managed copy of the entity.
     *
     * @throws ORMInvalidArgumentException
     */
    public function merge($entity)
    {
        if ( ! is_object($entity)) {
            throw ORMInvalidArgumentException::invalidObject('EntityManager#merge()' , $entity);
        }

        $this->_errorIfClosed();

        return $this->parentEm->getUnitOfWork()->merge($entity);
    }
    /**
     * {@inheritDoc}
     */
    public function lock($entity, $lockMode, $lockVersion = null)
    {
        $this->parentEm->getUnitOfWork()->lock($entity, $lockMode, $lockVersion);
    }
    /**
     * Determines whether an entity instance is managed in this EntityManager.
     *
     * @param object $entity
     *
     * @return boolean TRUE if this EntityManager currently manages the given entity, FALSE otherwise.
     */
    public function contains($entity)
    {
        return $this->parentEm->getUnitOfWork()->isScheduledForInsert($entity)
            || $this->parentEm->getUnitOfWork()->isInIdentityMap($entity)
            && ! $this->parentEm->getUnitOfWork()->isScheduledForDelete($entity);
    }
    /**
     * {@inheritDoc}
     */
    public function initializeObject($obj)
    {
        $this->parentEm->getUnitOfWork()->initializeObject($obj);
    }

    /**
     * Throws an exception if the EntityManager is closed or currently not active.
     *
     * @return void
     *
     * @throws ORMException If the EntityManager is closed.
     */
    protected function _errorIfClosed()
    {
        if (!$this->isOpen() || !$this->parentEm->isOpen()) {
            // throw ORMException::entityManagerClosed();
        }
    }



    public function getEntityName($entityName)
    {
        /*$class = str_replace('Nax', $this->brand, $entityName);
        if(class_exists($class)){
            $entityName = $class;
        }*/
        $entityName = BaseEntity::class($this->brand, $entityName);
        return $entityName;
    }

    /*static public function getInstance(EntityManager $em)
    {
        if(is_null(self::$_instance)){
            self::$_instance = new self($em);
        }
        return self::$_instance;
    }*/
}