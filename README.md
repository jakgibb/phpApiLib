
Hornbill PHP API Lib
========

Integration
===
Various Hornbill Integration methods are documented here: https://wiki.hornbill.com/index.php/Integration

Using API Keys
===

The easiest way to call Hornbill API's is to use an API Key. These are associated to users in the Administration Tool and are passed with every API Call removing the need to login.
```
//-- Initiate XmlmcService instance
$the_transport = new \Esp\Transport(self::ESP_ADDRESS, "xmlmc", "dav");
$mc            = new \Esp\MethodCall($the_transport);

//-- Set API Key
$mc->setAPIKey("521c5f8f138a43e58af87fea9f3a201c");

//-- Invoke session::getSessionInfo
$mc->invoke("session", "getSessionInfo");

//-- Get Session ID from the API Response
$strSessionId = $mc->getReturnParamAsString("sessionId");
```

Using user ID and password
===

Using Hornbill API's require an authenticated session. To create a session manually, without using API keys and the benefits they bring, you can call the session::userLogon API, providing username and password parameters. If you are using userLogon to create a session, rather than an API key, you MUST ensure that you also call session::userLogoff to end the session when your code has finished with it:

```
//-- Initiate XmlmcService instance
$the_transport = new \Esp\Transport(self::ESP_ADDRESS, "xmlmc", "dav");
$mc            = new \Esp\MethodCall($the_transport);

//-- Add Username
$mc->addParam("userId", "admin");

//-- Add Password (Plain text, API requires base64 encoded string, addPasswordParam() performs encoding.
$mc->addPasswordParam("password", "password");

//-- Invoke session::userLogon
$mc->invoke("session", "userLogon");

//-- Get the Session ID from the API Response
$strSessionID = $mc->getReturnParamAsString("sessionId");

//-- Invoke session::userLogoff
$mc->invoke("session", "userLogoff");

```
These strings need to be updated in the attached example, replacing 'yourinstancename' and 'yourapikey' with relevant strings:
```
const ESP_ADDRESS = "https://eurapi.hornbill.com/yourinstancename/";
const API_KEY = "yourapikey";
```
