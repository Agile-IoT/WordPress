<?php
/**
 * Created by IntelliJ IDEA.
 * User: firsti
 * Date: 4/27/18
 * Time: 12:13 PM
 */

if (!function_exists('write_log')) {
    function write_log($log)
    {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }
}

class Security
{

    private $read_caps = ["read", "read_post", "read_private_pages", "read_private_posts", "list_users", "export", "export_others_personal_data"];
    private $actions = array();
    private $policies = array();
    private $token;

    function getCaps()
    {
        global $wp_roles;
        return $wp_roles->roles;
    }

    function getLocalCaps()
    {
        $file = dirname(__FILE__) . '/caps.json';
        $data = file_get_contents($file);
        return json_decode($data, true);
    }

    function init()
    {
        //file_put_contents(dirname(__FILE__) . '/caps.json', json_encode($this->getCaps()));

        $caps = $this->getLocalCaps();
        foreach ($caps as $cap => $values) {
            foreach ($values as $key => $value) {
                if ($key == "capabilities") {
                    foreach ($value as $name => $val) {
                        if (in_array($name, $this->read_caps)) {
                            $this->actions[$name] = "read";
                        } else {
                            $this->actions[$name] = "write";
                        }
                    }
                    $values[$key] = $value;
                }
            }
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

    function evaluateWSO2($capability)
    {
        $user = wp_get_current_user();
        if ($user->ID > 0) {
            $data = array();
            $request = array();
            $action = array();
            $accessSubject = array();
            $resource = array();
            $roles = get_userdata($user->ID)->roles;
            $role = sizeof($roles) == 0 ? "user" : $roles[0];
            $method = strtolower($this->findMethod($capability));
            $action['Attribute'] = array(array(
                "AttributeId" => "urn:oasis:names:tc:xacml:1.0:action:action-id",
                "Value" => $method));
            $accessSubject['Attribute'] = array(array(
                "AttributeId" => "urn:oasis:names:tc:xacml:1.0:subject:role",
                "Value" => $role));
            $resource['Attribute'] = array(array(
                "AttributeId" => "urn:oasis:names:tc:xacml:1.0:resource:resource-id",
                "Value" => $capability));


            $request['Action'] = $action;
            $request['AccessSubject'] = $accessSubject;
            $request['Resource'] = $resource;
            $data['Request'] = $request;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_URL, SECURITY_HOST . SECURITY_PDP_PATH);
            curl_setopt($ch, CURLOPT_POST, 1);
            $headers = array();
            $headers[] = "Authorization: Basic " . base64_encode(SECURITY_USER_ID . ":" . SECURITY_USER_SECRET);
            $headers[] = "Content-Type: application/json";

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            //Ignore self signed SSL certificate warning
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                write_log('evaluateBatch Error:' . curl_error($ch));
            }
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $body = substr($response, $header_size);
            curl_close($ch);

            if ($httpcode == 200) {
                $result = json_decode($body)->Response[0]->Decision;

                //var_dump($result);
                $this->policies[$capability] = $result === "Permit";
                return $this->policies[$capability];
            }

        } else {
            write_log("No user found");
        }
    }

    function evaluateBatch()
    {
        $locks = array();
        foreach ($this->actions as $cap => $values) {
            $method = $this->findMethod($cap);
            $lock = array("entityId" => "wordpress", "entityType" => "/client", "field" => "actions." . $cap, "method" => $method);
            array_push($locks, $lock);
        }
        $data = new \stdClass();
        $data->actions = $locks;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_URL, SECURITY_HOST . SECURITY_PDP_PATH);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $_COOKIE["client_token"]
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $header_size);
        curl_close($ch);

        if ($httpcode == 200) {
            $result = json_decode($body)->result;
            $i = 0;
            foreach ($this->actions as $cap => $values) {
                $this->policies[$cap] = $result[$i++];
            }
        } else {
            $this->policies = array();
        }
        //var_dump($this->policies);
    }

    function evaluateSingle($capability)
    {
        $locks = array();

        $method = $this->findMethod($capability);
        $lock = array("entityId" => "wordpress", "entityType" => "/client", "field" => "actions." . $capability, "method" => $method);
        array_push($locks, $lock);

        $data = new \stdClass();
        $data->actions = $locks;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_URL, SECURITY_HOST . SECURITY_PDP_PATH);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $_COOKIE["client_token"]
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $header_size);
        curl_close($ch);

        if ($httpcode == 200) {
            $result = json_decode($body)->result;
            $this->policies[$capability] = $result[0];
        } else {
            $this->policies[$capability] = false;
        }
        //var_dump($this->policies);
    }

    function evaluate($capability)
    {
        if (isset($_COOKIE["client_token"])) {
            if (!isset($this->policies[$capability])) {
                if (SECURITY_SYSTEM == "WSO2") {
                    return $this->evaluateWSO2($capability);
                } else if (SECURITY_SYSTEM == "AGILE") {
                    if (SECURITY_CACHE) {
                        $this->evaluateBatch();
                    } else {
                        $this->evaluateSingle($capability);
                    }
                    if (isset($this->policies[$capability])) {
                        return $this->policies[$capability];
                    } else {
                        return false;
                    }
                }
            } else {
                return $this->policies[$capability];
            }
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

        curl_setopt($ch, CURLOPT_URL, SECURITY_HOST . "/oauth2/token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
            "grant_type" => "password",
            "username" => $username,
            "password" => $password,
            "scope" => "openid")));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_USERPWD, SECURITY_CLIENT_ID . ":" . SECURITY_CLIENT_SECRET);
        //Ignore self signed SSL certificate warning
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $headers = array();
        $headers[] = "Content-Type: application/x-www-form-urlencoded";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            write_log('AuthUser Error:' . curl_error($ch));
        }
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $header_size);

        curl_close($ch);
        if ($httpcode == 200) {
            $result = json_decode($body);
            $this->token = $result->access_token;
            $_SESSION['client_token'] = $this->token;
            $cookie_name = 'client_token';
            $cookie_value = $this->token;
            setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "/");
        } else {
            write_log("authUser: Could not get token from " . SECURITY_HOST);
            write_log("authUser: $response ");
        }
        return $this->token;
    }

    function getUser()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_URL, SECURITY_HOST . SECURITY_USER_INFO_PATH);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        //Ignore self signed SSL certificate warning
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $headers = array();
        $headers[] = "Authorization: Bearer " . $_SESSION['client_token'];

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // curl_setopt($ch, CURLOPT_USERPWD, SECURITY_CLIENT_ID . ":" . SECURITY_CLIENT_SECRET);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            write_log('getUser Error:' . curl_error($ch));
        }

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $header_size);
        curl_close($ch);
        if ($httpcode == 200) {
            $user = json_decode($body);
            return $user;
        } else {
            write_log("getUser: Could not get user info from security");
            write_log($response);
        }
        return $this->token;
    }
}