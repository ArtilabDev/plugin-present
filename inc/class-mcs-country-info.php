<?php

if (!defined('ABSPATH')) {
    exit;
}

class MCS_Country_Info {

    public function __construct()
    {
        add_action('init', array($this, 'mcs_process_country_info'));
    }

    public function mcs_process_country_info()
    {
        if (!isset($_COOKIE['mcs_info'])) {
            $ip = $this->get_client_ip();
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $ip_info = $this->get_ip_info_from_db($ip);
                if (!$ip_info) {
                    $ip_info = $this->fetch_ip_info($ip);
                    $this->store_ip_info_in_db($ip, $ip_info);
                }
                setcookie("mcs_info", $ip_info, strtotime('+90 days'), "/");
            }
        }
    }

    private function get_client_ip()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    private function get_ip_info_from_db($ip)
    {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT ip_info FROM ".$wpdb->prefix."mcs_country_info WHERE ip = %s", $ip));
    }

    private function fetch_ip_info($ip)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://cstat.nextel.com.ua:8443/tracking/registration/ipData?ip=".$ip);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        $output = curl_exec($ch);

        // Default to USD and US in case of an error.
        if (curl_errno($ch)) {
            $output = '{"currency":"USD","countryCode":"US"}';
        }
        curl_close($ch);
        return $output;
    }

    private function store_ip_info_in_db($ip, $ip_info)
    {
        // Store the fetched IP info in database.
        global $wpdb;
        $wpdb->insert($wpdb->prefix."mcs_country_info", [
            'ip' => $ip,
            'ip_info' => $ip_info,
            'date' => time()
        ]);
    }
}
