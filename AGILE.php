<?php
/**
 * Created by IntelliJ IDEA.
 * User: firsti
 * Date: 4/27/18
 * Time: 12:13 PM
 */

class AGILE
{

    private $read_caps = ["read", "read_post", "read_private_pages", "read_private_posts", "list_users", "export", "export_others_personal_data"];
    private $actions = array();
    private $token;

    function getCaps()
    {
        global $wp_roles;
        return $wp_roles->roles;
    }

    function init()
    {
        //file_put_contents(dirname(__FILE__) . '/caps.json', json_encode($this->getCaps()));

        $file = dirname(__FILE__) . '/caps.json';
        $data = file_get_contents($file);
        $caps = json_decode($data, true);
        foreach ($caps as $cap => $values) {
            foreach ($values as $key => $value) {
                if ($key == "capabilities") {
                    foreach ($value as $name => $val) {
                        if (in_array($name, $this->read_caps)) {
                            $this->actions[$name] = "READ";
                        } else {
                            $this->actions[$name] = "WRITE";
                        }
                    }
                    $values[$key] = $value;
                }
            }
            // $this->actions[$cap] = $values;
        }
        //var_dump($this->actions);
        //if(!($this->hasToken())) {
        if (!isset($_SESSION['token'])) {
            $this->register();
        } else {
            $this->token = $_SESSION['token'];
        }
        //} else {
        //  echo $this->token;
        //}
    }


    function register()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_URL, "http://" . AGILE_HOST . "/oauth2/token");
        curl_setopt($ch, CURLOPT_USERPWD, AGILE_ID . ":" . AGILE_SECRET);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array("grant_type" => "client_credentials")));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $header_size);
        curl_close($ch);
        if ($httpcode == 200) {
            $result = json_decode($body);
            $this->token = $result->access_token;
            $_SESSION['token'] = $this->token;
        } else {
            echo "Could not get token from AGILE";
        }
    }

    function hasToken()
    {
        return is_null($this->token);
    }

    function findMethod($capability)
    {
        if (isset($this->actions[$capability])) {
            return $this->actions[$capability];
        } else {
            return false;
        }
    }


    function evaluate($capability)
    {
        $method = $this->findMethod($capability);
        $locks = array("entityId" => AGILE_ID, "entityType" => "client", "field" => "database", "method" => $method);
        $data = new \stdClass();
        $data->actions = array($locks);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_URL, "http://" . AGILE_HOST . "/api/v1//pdp/batch/");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->token
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $header_size);
        curl_close($ch);
        if ($httpcode == 200) {
            $result = json_decode($body);
            return $result->result[0];
        } else {
            return false;
        }
    }

    function getPolicies()
    {
        return $this->actions;
    }

    function authUser($username, $password)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_URL,"http://" . AGILE_HOST . "/oauth2/usertoken");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array(
            "grant_type" => "password",
            "username" => $username,
            "password" => $password)));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $header_size);
        curl_close($ch);
        if ($httpcode == 200) {
            $result = json_decode($body);
            $this->token = $result->access_token;
            $_SESSION['token'] = $this->token;
            $cookie_name = 'token';
            $cookie_value = $this->token;
            setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "/");
        } else {
            echo "Could not get token from AGILE";
        }
        return $this->token;
    }

    function getUser($userid)
    {
        $authorization = "Authorization: Bearer " . $_SESSION['token'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_URL,"http://" . AGILE_HOST . "/api/v1/entity/user/");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization));
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $header_size);
        curl_close($ch);
        if ($httpcode == 200) {
            $user = json_decode($body);
            return $user;
        } else {
            echo "Could not get token from AGILE";
        }
        return $this->token;
    }
}