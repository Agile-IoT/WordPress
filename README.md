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
On the next page (edit page) you can configure the service. Expand the "Inbound Authentication Configuration" tab and expand the "OAuth/OpenID Connect Configuration". Click on "Configure".
Enter any callback Url, e. g. localhost and click on "add". 
After the service was successfully added, you can view the OAuth Client Key and Oauth Client Secret of the service. 

Copy both to the configuration file in WordPress as the client credentials, e. g.:

    define( 'SECURITY_CLIENT_ID', 'hgm6eA_B8ZGwIFmJ9iJf_3hE0Jsa' );
    define( 'SECURITY_CLIENT_SECRET', 'E4LROiG6AUE4ZfmCWIaOtW3LyzAa' ); 

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