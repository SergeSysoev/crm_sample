<?php
namespace NaxCrmBundle\Command;

use Doctrine\Common\Collections\Criteria;
use NaxCrmBundle\Entity\Message;
use NaxCrmBundle\Modules\SlackBot;

class SendEmailsCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            // the name of the command (the part after "app/console")
            ->setName('app:send-emails')

            // the short description shown while running "php app/console list"
            ->setDescription('Send all emails in queue.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('')
        ;
    }

    protected function exec()
    {
        $this->printTitle('Sending emails ...');

        $root_path = dirname(dirname(dirname(__DIR__))).'/';

        $repo = $this->getRepository(Message::class());
        $x = Criteria::expr();
        $criteria = Criteria::create();
        $criteria->setMaxResults(20);
        $criteria->where(
            $x->andX(
                $x->lte('sendAt', new \DateTime()),
                $x->eq('status', Message::STATUS_NEW)
            )
        );
        /** @var Message[] $emails */
        $emails = $repo->matching($criteria);
        /** @var \Swift_Mailer $mailer */

        $cfg = $this->getParameter('mailer');
        // $env = $this->getParameter('kernel.environment');
        // $this->info("Env{$env}");
        if(empty($cfg)){
            $this->error('Err: Can`t read parameters->(array)mailer');
            return;
        }
        $transport = \Swift_SmtpTransport::newInstance()
            ->setHost($cfg['host'])
            ->setPort($cfg['port'])
            ->setUsername($cfg['user'])
            ->setPassword($cfg['password'])
            ->setEncryption($cfg['secure']?:'tls');
        $mailer = \Swift_Mailer::newInstance($transport);

        $c = count($emails);
        $this->out("Total emails to be sent: {$c}");
        $s = 0;
        foreach ($emails as $email) {
            $message = \Swift_Message::newInstance()
                ->setSubject($email->getSubject())
                ->setFrom([$cfg['user'] => $cfg['from_alias']])
                ->setReplyTo($email->getReplyTo())
                ->setTo($email->getEmail())
                ->setBody($email->getHtml(), 'text/html')
            ;

            $attach_path = false;
            if(!empty($email->getAttachment())){
                $attachment = explode(';',$email->getAttachment());
                $attach_path = $this->getParameter('uploads_directory').'/attachments/'.$attachment[0];
                if(file_exists($attach_path)){
                    $message->attach(
                        \Swift_Attachment::fromPath($attach_path)
                         ->setFilename((empty($attachment[1])?$attachment[0]:$attachment[1]))
                    );
                }
                else{
                    SlackBot::send('Can`t read attachment', ['attach_path' => $attach_path,], SlackBot::WARN);
                    $attach_path = false;
                }
            }

            if ($mailer->send($message)) {
                ++$s;
                $email->setStatus(Message::STATUS_SENT);
                $email->setWasSent(new \DateTime());
                // if($attach_path){
                //     unlink($attach_path);
                // }
            }
            else {
                $email->setStatus(Message::STATUS_ERROR);
            }
        }
        $this->getEm()->flush();
        $this->info("{$s}/{$c} Emails were sent successfully");
    }
}