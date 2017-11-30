<?php
/*!
 * @file
 * Helper for repositories
 *
 * Implement common functions for queries
 */
namespace NaxCrmBundle\Modules;

use Symfony\Component\Yaml\Yaml;


class SlackBot
{
    const URL = 'https://hooks.slack.com/services/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';

    const INFO = 0;
    const WARN = 1;
    const ERR = 2;
    const CRIT = 3;

    static $types = [
        self::INFO => 'Info',
        self::WARN => 'Warning',
        self::ERR =>  'Error',
        self::CRIT => 'Critical',
    ];
    static $colors = [
        self::INFO => '#0000BB',
        self::WARN => '#FFBB00',
        self::ERR =>  '#BB0000',
        self::CRIT => '#FF0000',
    ];
    static $icons = [
        self::INFO => ':information_source:',
        self::WARN => ':warning:',
        self::ERR =>  ':exclamation:',
        self::CRIT => ':bangbang:',
    ];
    static $places = [
        '52.57.39.194' => 'prod',
        '172.31.8.144' => 'prod',

        '52.57.141.102' => 'legal',
        '172.31.13.54' => 'legal',

        '52.57.18.199' => 'stage',
        '172.31.8.54' => 'stage',

        '::1' => 'local',
        '127.0.0.1' => 'local',
    ];

    public static function send($text, $data = [], $type = self::WARN, $channel='#crm-info'){
        $params = self::getParams();
        if(!empty($params['ignore_slack'])){
            return false;
        }
        $params['brand'] = strtoupper($params['brand']);
        $params['env'] = str_replace('dev', 'prod_debug', $params['env']);// =)

        $pre_text = "*{$params['brand']} [{$params['env']}]*";
        if(!empty($data)){
            $text.= "\n```".json_encode($data, JSON_PRETTY_PRINT).'```';
        }

        if($params['env']!='stage' && in_array($type,[
            self::ERR,
            self::CRIT,
        ])){
            $pre_text.=' <!here>';
        }

        if(!empty($_SERVER['SERVER_ADDR'])){
            $place = $_SERVER['SERVER_ADDR'];
        }
        else{
            $place = gethostbyname(gethostname());
        }

        if(!empty(self::$places[$place])){
            $place.= ' ('.self::$places[$place].')';
        }
        else{
            $place.= ' (unknown)';
        }

        $footer = 'unknown referer';
        if(!empty($_SERVER['HTTP_REFERER'])){
            $footer = $_SERVER['HTTP_REFERER'];
        }
        $_HEADERS = getallheaders();
        if(!empty($_HEADERS['X-Referer'])){
            $footer = $_HEADERS['X-Referer'];
        }

        $mes = [
            'channel' => $channel,
            'username' => "{$params['brand']}-CRM-Bot",
            'icon_emoji' => ":{$params['brand']}:",

            'attachments' => [[
                'pretext' => ":{$params['brand']}: {$pre_text}",
                'color' => self::$colors[$type],
                'title' => self::$icons[$type].' '.self::$types[$type],
                'author_name' => $place,
                'text' => $text,
                'mrkdwn_in' => [
                    'text',
                    'pretext',
                ],
                'footer' => $footer,
            ]]
        ];
        /*$mes = [
            'text' => $text,
            'icon_emoji' => $icon,
            'channel' => $channel,

            'username' => 'CRM-Bot',
            'link_names' => 1,
            'mrkdwn' => true,
        ];*/
        return self::curl_send($mes);
    }

    public static function curl_send($data){
        if(is_array($data)){
            $data = json_encode($data);
        }
        $headers = [
            'Content-type' => 'application/json',
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::URL);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);

        try{
            $res = curl_exec($ch);
            if($err = curl_errno($ch)){
                \NaxCrmBundle\Debug::$messages[] = ['slak_bot' => 'ERROR: connection error: '.curl_error($ch)];
                return false;
            }
        }
        catch(\Exception $e){
            \NaxCrmBundle\Debug::$messages[] = ['slak_bot' => 'ERROR: '.$e->getMessage()];
            return false;
        }
        return true;
    }

    public static function getParams(){
        $params = Yaml::parse(file_get_contents(dirname(__FILE__) . '/../../../app/config/parameters.yml'));
        $params = $params['parameters'];
        return $params;
    }
}