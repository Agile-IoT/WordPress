# WordPress with custom security framework
This WordPress fork uses either [AGILE Security](https://github.com/agile-iot/agile-security) or [WSO2IS](https://wso2.com/identity-and-access-management) (WSO2 Identity Server) as its identity and access control framework.

## WSO2IS
### Setup
First include WSO2IS in your stack:

      wso2is:
        image: isim/wso2is
        container_name: wso2is
        restart: always
        ports:
          - 9444:9443/tcp

After you started the stack by ```docker-compose up``` you can start configuring WSO2IS such that it is usable by WordPress.

### Configuration
For the configuration you need to login as an admin user. Default admin credentials are ```admin:admin```.
#### 1. Add users
Go to "Users and Roles" -> "Add" -> "Add New User" and fill in the required fields. On the next page, you can assign a role to the user. Finish the process.
#### 2. Add a and configure a service provider
##### 2.1. Add the service provider
Go to "Service Providers" -> "Add". Enter the service provider name, e. g. "wordpress", and register the service.
##### 2.2 Configure the authentication mechanism
###### 2.2.1 Create the service and client credentials
On the next page (edit page) you can configure the service. Expand the "Inbound Authentication Configuration" tab and expand the "OAuth/OpenID Connect Configuration". Click on "Configure".
Enter any callback Url, e. g. localhost and click on "add". 
After the service was successfully added, you can view the OAuth Client Key and Oauth Client Secret of the service. 

Copy both to the configuration file (wp-config.php) in WordPress as the client credentials, e. g.:

    define( 'SECURITY_CLIENT_ID', 'hgm6eA_B8ZGwIFmJ9iJf_3hE0Jsa' );
    define( 'SECURITY_CLIENT_SECRET', 'E4LROiG6AUE4ZfmCWIaOtW3LyzAa' ); 
###### 2.2.2 Set the role claim
In the same view of the service provider edit page, under "Claim Configuration" select "Use Local Claim Dialect", add a claim URI by clicking on "Add Claim Uri" and chose "http://wso2.org/claims/role" in the "Requested Claims" section. 

###### 2.2.3 Save the service provider configuration
Click on save or update to save the configuration.

##### 2.3 Add a service user

Go to "Users and Roles" -> "Add" -> "Add New User" and fill in the required fields. Since this user will be used by wordpress, you can name give it the name "wordpress". 
On the next page, you should assign the admin role to the new user. If you don't include the admin role, the default role of the user in WordPress will be ```subscriber```. Finish the process.

##### 2.4 Add the service user credentials to the configuration file and adjust the rest of the WordPress configuration

In wp-config.php, you need to add the service user credentials, e. g.:

    define( 'SECURITY_USER_ID', 'wordpress');
    define( 'SECURITY_USER_SECRET', 'k8LZ6t4&fO2s');

The configuration file should containing following lines:

    define( 'SECURITY_SYSTEM', 'WSO2');
    define( 'SECURITY_HOST', 'https://wso2is:9443' );
    define( 'SECURITY_CLIENT_ID', 'hgm6eA_B8ZGwIFmJ9iJf_3hE0Jsa' );
    define( 'SECURITY_CLIENT_SECRET', 'E4LROiG6AUE4ZfmCWIaOtW3LyzAa' );
    define( 'SECURITY_USER_ID', 'wordpress');
    define( 'SECURITY_USER_SECRET', 'k8LZ6t4&fO2s');
    define( 'SECURITY_USER_INFO_PATH', '/oauth2/userinfo?schema=openid');
    define( 'SECURITY_PDP_PATH', '/api/identity/entitlement/decision/pdp');
    define( 'SECURITY_CACHE', false);  

#### 3. Add roles to userinfo

Go to "Claims" -> "Add External Claim"

1. Choose "http://wso2.org/oidc/claim" for the Dialect URI
2. Set a name for the external claim, e. g. "roles"
3. Choose "http://wso2.org/claims/role" for the Mapped Local Claim

#### 4. Configure OIDC
Go to "Registry" -> "Browse" -> "_system" -> "config" -> "oidc". Expand the "Properties" tab. You should see a "openid" entry. Click on "edit" and add "roles," (or the name for the claim you set in the step before). The result should similar to the following example:

    roles,sub,email,email_verified,name,family_name,given_name,middle_name,nickname,preferred_username,profile,picture, website,gender,birthdate,zoneinfo,locale,updated_at,phone_number,phone_number_verified,address,street

#### 5. Add WordPress policies

Add policies for the WordPress capabilities. You can find example policies for WordPress in the [capabilities XML file](https://github.com/firsti/WordPress/blob/master/caps.xml).
You can upload the sample policies by going to "Entitlement" -> "Policy Administration" -> "Add New Entitlement Policy" -> "Import Existing Policy" and chose the sample policies file.

#### 6. Done
Now WordPress uses WSO2IS as the identity and authentication service.

## AGILE

Before starting AGILE security, add WordPress default roles. For this modify ```$DATA/security/idm/agile-idm-core-conf.js```, where ```$DATA``` is you AGILE path, e.g. ```~/.agile```.

Modify the role attribute of the ```/user``` type and add the default WordPress roles ```"administrator", "editor", "author", "contributor"``` and ```"subscriber"```, such that it looks as follows:

    {
       "id": "/user",
       "type": "object",
       "properties": {
         "user_name": {
           "type": "string"
         },
         "auth_type": {
           "type": "string",
           "enum": ["agile-local"]
         },
         "password": {
           "type": "string"
         },
         "role": {
           "type": "string",
           "enum": ["admin", "administrator", "editor", "author", "contributor", "subscriber"]
         },
         "credentials": {
           "type": "object",
           "additionalProperties": true
         }
       },
       "required": ["user_name", "auth_type"]
     }

### Configuration

Change the configuration in wp-config.php to fit AGILE, e. g.:

    define( 'SECURITY_SYSTEM', 'AGILE');
    define( 'SECURITY_HOST', 'http://agile-security:3000' );
    define( 'SECURITY_CLIENT_ID', 'wordpress' );
    define( 'SECURITY_CLIENT_SECRET', 'secret' );
    define( 'SECURITY_USER_ID', '');
    define( 'SECURITY_USER_SECRET', '');
    define( 'SECURITY_USER_INFO_PATH', '/oauth2/api/userinfo/');
    define( 'SECURITY_PDP_PATH', '/api/v1//pdp/batch/');
    define( 'SECURITY_CACHE', false);


#### 1. Add the WordPress client 
Add a client with the credentials set in the configuration file described before, e. g. id ```wordpress``` with the corresponding secret.

For this go to "device manager" -> "client" -> "add new client" and fill in the required fields.

#### 2. Add WordPress policies to the client

To add the policies to decide whether a WordPress user is allowed to do something or not, you need to set them to the wordpress client. For this, go to client overview ("client" tab).
At the wordpress client click on "policies" and the policies.

To add a default policies, you can run the script [addPolicies.js](https://github.com/Agile-IoT/WordPress/blob/master/addPolicies.js). This will add the policies according to [caps.json](https://github.com/Agile-IoT/WordPress/blob/master/caps.json). 
You can run with

    node addPolicies.js $TOKEN
    
where ```$TOKEN``` is an AGILE access token. You can use the access token you get, when you login in AGILE-OSJS. You need to be the owner or an administrator to add policies to the wordpress client by default.

#### 3. Add WordPress users

Go to "user" -> "add new user" and fill in the fields to the desired values. For the role attribute chose a WordPress role, e. g. ```Editor```.
If you set the role to a non-WordPress role, the default role of the user in WordPress will be ```subscriber```.

#### 4. Done

Now WordPress uses AGILE as the identity and authentication service. 