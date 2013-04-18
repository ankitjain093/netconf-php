<?php

error_reporting(0);

include('CommitException.php');
include('LoadException.php');
include('XML.php');
include('NetconfException.php');

class Device {
    var $hostName;
    var $userName;
    var $password;
    var $port;
    var $helloRpc;
    var $stream;
    var $isConnected;
    var $lastRPCReply;
    
    /**
    * A <code>Device</code> is used to define a Netconf server.
    * <p>
    * Typically, one
    * <ol>
    * <li>creates a {@link #Device(String,String,String) Device} 
    * object.</li>
    * <li>perform netconf operations on the Device object.</li>
    * <li>Finally, one must close the Device and release resources with the 
    * {@link #close() close()} method.</li>
    * </ol>
    */
    public function Device() {
        if(func_num_args() == 4) {
            if(is_array(func_get_arg(3))) {
                $this->helloRPC = $this->createHelloRPC(func_get_arg(3));
                $this->port = 830;
            }

            else {
                $this->port = func_get_arg(3);
                $this->helloRPC = $this->defaultHelloRPC();
            }
        }
        else if (func_num_args() == 5)
        {
            if (is_array(func_get_arg(3))) {
                $this->helloRPC = $this->createHelloRPC(func_get_arg(3));
                $this->port = func_get_arg(4);
            }
            else {
                $this->port = func_get_arg(3);
                $this->helloRPC = $this->createHelloRPC(func_get_arg(4));
            }
        }
        else {
            $this->port = 830;
            $this->helloRPC = $this->defaultHelloRPC();
        }
        $this->hostName = func_get_arg(0);
        $this->userName = func_get_arg(1);
        $this->password = func_get_arg(2);
        $this->isConnected = false;
    }
   
    /**
    *Prepares a new <code?Device</code> object, either with default 
    *client capabilities and default port 830, or with user specified
    *capabilities and port no, which can then be used to perform netconf 
    *op!erations.
    */
    public function connect() {
        $this->stream = expect_popen("ssh $this->userName@$this->hostName -p $this->port -s netconf");
        $flag = true;
        while ($flag) {
            switch (expect_expectl($this->stream,array (
                array("password:","PASSWORD"),
                array("yes/no)?","YESNO"),
                array("passphrase","PASSPHRASE"),
                array("]]>]]>","NOPASSPHRASE"),
                array(" ","SHELL"),
                  ))) {
                case "PASSWORD":
                    fwrite($this->stream,$this->password."\n");
                    switch (expect_expectl($this->stream,array (
                        array("password:","PASSWORD"),
                        array("]]>]]>","hello"),
                        ))) { 
                        case "PASSWORD":
                            throw new NetconfException("Wrong username or password");
                        case "hello":
                            $this->sendHello($this->helloRPC);
                            break;
                    }
                    $flag = false;
                    break;
                case "PASSPHRASE":
                    fwrite($this->stream,$this->password."\n");
                    switch (expect_expectl($this->stream,array (
                        array("password:","PASSWORD"),
                        array("]]>]]>","hello"),
                        ))) {
                        case "PASSWORD":
                            throw new NetconfException("Wrong username or password");
                        case "hello":
                            $this->sendHello($this->helloRPC);
                            break;
                    }
                    $flag = false;
                    break;
                case "NOPASSPHRASE":
                    echo "hello";
                    $this->sendHello($this->helloRPC);
                    $flag = false;
                    break;
                case "YESNO":
                    fwrite($this->stream,"yes\n");
                    break;
                case "SHELL":
                    break;
                case "WRONG":
                    throw new NetconfException("Wrong username or password");
                    break;
                default:
                    throw new NetconfException("Device not found");
                }
        }
        $this->isConnected = true;
    }

   /**
   Sends the Hello capabilities to the netconf server.
   */
   private function sendHello($hello) {
        $reply = "";
        $reply = $this->getRPCReply($hello);
        $serverCapability = $reply;
        $this->lastRPCReply = $reply;
    }

    /**
    *Sends the RPC as a strung and returns the response as a string.
    */
    private function getRPCReply($rpc) {
        $rpc_reply = "";
        fwrite($this->stream,$rpc."\n");
        while (1) {
            $line = fgets($this->stream);
            if (strncmp($line,"<rpc>",5)==0)
                if (strpos($line,"]]>]]>"))
                    continue;
                else {
                    while (1) {
                        $line = fgets($this->stream);
                        if (strpos($line,"]]>]]>")) {
                            $line = fgets($this->stream);
                            break;
                        }
                    }
                }
            if ((strncmp($line,"]]>]]>",6))==0)
                break;
            $rpc_reply.=$line;
        }
        return $rpc_reply;
    }

    /**
    *Sends RPC(as XML object or as a String) over the default Netconf session 
    *and get the response as an XML object.
    *<p>
    *@param rpc
    *       RPC content to be sent. 
    *@return RPC reply sent by Netconf server.
    */
    public function executeRPC($rpc) {
        if ($rpc==null)
            die("Null RPC");
        if (gettype($rpc) == "string") {
            if (!$this->startsWith($rpc,"<rpc>")) {
                $rpc = "<rpc><".$rpc."/></rpc>";
                $rpc.="]]>]]>";
                echo $rpc;
            }
            $rpc_reply_string = $this->getRPCReply($rpc);
        }
        else {
            $rpcString = $rpc->toString();
            $rpc_reply_string = $this->getRPCReply($rpcString);
        }
        $this->lastRPCReply = $rpc_reply_string;
        $rpc_reply = $this->convertToXML($rpc_reply_string);
        return $rpc_reply;
    }

    /**
    *Converts the string to XML.
    *@return XML object.
    */
    private function convertToXML($rpc_reply) {
        $dom = new DomDocument();
        $xml = $dom->loadXML($rpc_reply);
        if (!$xml)
            return false;
        $root = $dom->documentElement;
        return new XML($root,$dom);
    }

    /*
    @retrun the last RPC Reply sent by Netconf server.
    */
    public function getLastRpcReply() {
        return $this->lastRPCReply;
    }

    /**
    *sets the username of the Netconf server.
    *@param username
    *     is the username which is to be set
    */
    public function setUserName($username) {
        if ($this->isConnected)
            throw new NetconfException("Can't change username on a live device. Close the device first.");
        else
            $this->userName =   $username;
    }

    /**
    *sets the hostname of the Netconf server.
    *@param hostname
    *      is the hostname which is to be set.
    */
    public function setHostName($hostname) {
        if ($this->isConnected)
            throw new NetconfException("Can't change hostname on a live device. Close the device first");
        else
            $this->hostName = $hostname;
    }

    /**
    *     sets the password of the Netconf server.
    *@param password
    *     is the password which is to be set.
    */
    public function setPassword($password) {
        if ($this->isConnected)
            throw new NetconfException("Can't change the password for the live device. Close the device first");
        else
            $this->password = $password;
    }

    /**
    *     sets the port of the Netconf server.
    *@param port
    *     is the port no. which is to be set.
    */
    public function setPort($port) {
        if ($this->isConnected)
            throw new NetconfException("Can't change the port no for the live device. Close the device first");
        else
            $this->port = $port;
    }

    /**
    *Set the client capabilities to be advertised to the Netconf server.
    *@param capabilities 
    *       Client capabilities to be advertised to the Netconf server.
    *
    */
    public function setCapabilities($capabilities) {
        if($capabilities == null)
            die("Client capabilities cannot be null");
        if($this->isConnected) {
            throw new NetconfException("Can't change clien capabilities on a live device. Close the device first.");
        $this->helloRpc = $this->createHelloRPC($capabilities);
        }
    }

    /**
    *Check if the last RPC reply returned from Netconf server has any error.
    *@return true if any errors are found in last RPC reply.
    */
    public function hasError() {
        if(!$this->isConnected)
            die("No RPC executed yet, you need to establish a connection first");
        if ($this->lastRPCReply == "" || !(strstr($this->lastRPCReply,"<rpc-error>")))
            return false;
        $reply = $this->convertToXML($this->lastRPCReply);
        $tagList[0] = "rpc-error";
        $tagList[1] = "error-severity";
        $errorSeverity = $reply->findValue($tagList);
        if ($errorSeverity != null && $errorSeverity == "error")
            return true;
        return false;
    }

     /**
    *Check if the last RPC reply returned from Netconf server has any warning.
    *@return true if any warnings are found in last RPC reply.
    */
    public function hasWarning() {
        if(!$this->isConnected)
            die("No RPC executed yet, you need to establish a connection first");
        if ($this->lastRPCReply == "" || !(strstr($this->lastRPCReply,"<rpc-error>")))
            return false;
        $reply = $this->convertToXML($this->lastRPCReply);
        $tagList[0] = "rpc-error";
        $tagList[1] = "error-severity";
        $errorSeverity = $reply->findValue($tagList);
        if ($errorSeverity != null && $errorSeverity == "warning")
            return true;
        return false;
    }


    /**
    *Check if the last RPC reply returned from the Netconf server.
    *contain &lt;ok&gt; tag
    *@return true if &lt;ok&gt; tag is found in last RPC reply.
    */
    public function isOK() {        
        if(!$this->isConnected)
            die("No RPC executed yet, you need to establish a connection first");
        if ($this->lastRPCReply!=null && strstr($this->lastRPCReply,"<ok/>"))
            return true;
        return false;
    }

    /**
    *Locks the candidate configuration.
    *@return true if successful.
    */
    public function lockConfig() {
        $rpc = "<rpc>";
        $rpc.= "<lock>";
        $rpc.="<target>";
        $rpc.="<candidate/>";
        $rpc.="</target>";
        $rpc.="</lock>";
        $rpc.="</rpc>";
        $rpc.="]]>]]>\n";
        $rpcReply = $this->getRPCReply($rpc);
        $this->lastRPCReply = $rpcReply;
        if ($this->hasError() || !$this->isOK())
            return false;
        return true;
    }

    /**
    *Unlocks the candidate configuration.
    *@return true if successful.
    */
    public function unlockConfig() {
        $rpc = "<rpc>";
        $rpc.="<unlcok>";
        $rpc.="<target>";
        $rpc.="<candidate/>";
        $rpc.="</target>";
        $rpc.="</unlcok>";
        $rpc.="</rpc>";
        $rpc.="]]>]]>\n";
        $rpcReply = $this->getRPCReply($rpc);
        $this->lastRPCReply = $rpcReply;
        if ($this->hasError() || !$this->isOK())
            return false;
        return true;
    }

    private function startsWith($string,$substring) {
        trim($substring);
        trim($string);
        $length = strlen($substring);
        if (substr($string,0,$length)===$substring)
            return true;
        return false;
    }

    /**
    *Loads the candidate configuration, Configuration should be in XML format.
    *@param configuration
    *        Configuration, in XML fromat, to be loaded. For eg:
    *        &lt;configuration&gt;&lt;system&gt;&lt;services&gt;&lt;ftp/&gt;&lt;/services&gt;&lt;/system&gt;&lt;/configuration&gt;
    *       will load 'ftp' under the 'systems services' hierarchy.
    *@param loadType
    *       You can choose "merge" or "replace" as the loadType.
    */
    public function loadXMLConfiguration($configuration,$loadType) {
        if ($loadType == null || (!($loadType == "merge") && !($loadType == "replace")))
            die("'loadType' argument must be merge|replace\n");
        if ($this->startsWith($configuration,"<?xml version"))
            $configuration = preg_replace('/\<\?xml[^=]*="[^"]*"\?\>/', "", $configuration);
        else if (!($this->startsWith($configuration,"<configuration>")))
            $configuration = "<configuration>".$configuration."</configuration>";
        $rpc = "<rpc>";
        $rpc.="<edit-config>";
        $rpc.="<target>";
        $rpc.="<candidate/>";
        $rpc.="</target>";
        $rpc.="<default-operation>";
        $rpc.=$loadType;
        $rpc.="</default-operation>";
        $rpc.="<config>";
        $rpc.=$configuration;
        $rpc.="</config>";
        $rpc.="</edit-config>";
        $rpc.="</rpc>";
        $rpc.="]]>]]>\n";
        $rpcReply = $this->getRPCReply($rpc);
        $this->lastRPCReply = $rpcReply;
        if ($this->hasError() || !$this->isOK())
            throw new LoadException("Load operation returned error");
    }

    /**
    *Loads the candidate configuration, Configuration should be in text/tree format.
    *@param configuration 
    *      Configuration, in text/tree format, to be loaded. 
    *      For example,
    *       "system{
    *          services{
    *              ftp;
    *           }
    *       }"
    *       will load 'ftp' under the 'systems services' hierarchy.
    *@param loadType
    *        You can choose "merge" or "replace" as the loadType.
    */
    public function loadTextConfiguration($configuration,$loadType) {
        if ($loadType == null || (!($loadType == "merge") && !($loadType == "replace")))
            die ("'loadType' argument must be merge|replace\n");
        $rpc = "<rpc>";
        $rpc.="<edit-config>";
        $rpc.="<target>";
        $rpc.="<candidate/>";
        $rpc.="</target>";
        $rpc.="<default-operation>";
        $rpc.=$loadType;
        $rpc.="</default-operation>";
        $rpc.="<config-text>";
        $rpc.="<configuration-text>";
        $rpc.=$configuration;
        $rpc.="</configuration-text>";
        $rpc.="</config-text>";
        $rpc.="</edit-config>";
        $rpc.="</rpc>";
        $rpc.="]]>]]>\n";
        $rpcReply = $this->getRPCReply($rpc);
        $this->lastRPCReply = $rpcReply;
        if ($this->hasError() || !$this->isOK())
            throw new LoadException("Load operation returned error");
    }

    /**
    *Loads the candidate configuration, Configuration should be in set format.
    *NOTE: This method is applicable only for JUNOS release 11.4 and above.
    *@param configuration
    *       Configuration, in set format, to be loaded. For example,
    *       "set system services ftp"
    *       will load 'ftp' under the 'systems services' hierarchy.
    *To load multiple set statements, separate them by '\n' character.
    */
    public function loadSetConfiguration($configuration) {
        $rpc = "<rpc>";
        $rpc.="<load-configuration action=\"set\">";
        $rpc.="<configuration-set>";
        $rpc.=$configuration;
        $rpc.="</configuration-set>";
        $rpc.="</load-configuration>";
        $rpc.="</rpc>\n";
        $rpcReply = $this->getRPCReply($rpc);
        $this->lastRPCReply = $rpcReply;
        if ($this->hasError() || !$this->isOK())
            throw new LoadException("Load operation returned error");
    }

    /**
    *Commit the candidate configuration.
    */
    public function commit() {
        $rpc = "<rpc>";
        $rpc.="<commit/>";
        $rpc.="</rpc>";
        $rpc.="]]>]]>\n"; 
        $rpcReply = $this->getRPCReply($rpc);
        $this->lastRPCReply = $rpcReply;
        if ($this->hasError() || !$this->isOK())
            throw new CommitException("Commit operation returned error");
    }

    /**
    *Commit the candidate configuration, temporarily. This is equivalent of
    'commit confirm'
    *@param seconds 
    *        Time in seconds, after which the previous active configuratio
    *        is reverted back to.
    */
    public function commitConfirm($seconds) {
        $rpc = "<rpc>";
        $rpc.="<commit>";
        $rpc.="<confirmed/>";
        $rpc.="<confirm-timeout>".$seconds."</confirm-timeout>";
        $rpc.="</commit>";
        $rpc.="</rpc>";
        $rpc.="]]>]]>\n";
        $rpcReply = $this->getRPCReply($rpc);
        $this->lastRPCReply = $rpcReply;
        if ($this->hasError() || !$this->isOK())
            throw new CommitException("Commit operation returned error");
    }

    /**
    *Validate the candidate configuration.
    *@return true if validation successful.
    */
    public function validate() {
        $rpc = "<rpc>";
        $rpc.="<validate>";
        $rpc.="<source>";
        $rpc.="<candidate/>";
        $rpc.="</source>";
        $rpc.="</validate>";
        $rpc.="</rpc>";
        $rpc.="]]>]]>\n";
        $rpcReply = $this->getRPCReply($rpc);
        $this->lastRPCReply = $rpcReply;
        echo $rpcReply;
        if ($this->hasError() || !$this->isOK())
            return false;
        return true;
    }

    /**
    *Reboot the device corresponding to the Netconf Session.
    *@return RPC reply sent by Netconf servcer.
    */
    public function reboot() {
        $rpc = "<rpc>";
        $rpc.="<request-reboot/>";
        $rpc.="</rpc>";
        $rpc.="]]>]]>\n";
        $rpcReply = $this->getRPCReply($rpc);
        return $rpcReply;
    }

    /**
    *This method should be called for load operations to happen in 'private' mode.
    *@param mode
    *       Mode in which to open the configuration.
    *       Permissible mode(s) : "private"
    */
    public function openConfiguration($mode) {
        $rpc = "<rpc>";
        $rpc.="<open-configuration>";
        $rpc.=$mode;
        $rpc.="</open-configuration>";
        $rpc.="</rpc>";
        $rpc.="]]>]]>\n";
        $rpcReply = $this->getRPCReply($rpc);
        $this->lastRPCReply = $rpcReply;
    }

    /**
    *This method should be called to close a private session, in case its started.
    */
    public function closeConfiguration() {
        $rpc = "<rpc>";
        $rpc.="<close-configuration/>";
        $rpc.="</rpc>";
        $rpc.="]]>]]>\n";
        $rpcReply = $this->getRPCReply($rpc);
        $this->lastRPCReply = $rpcReply;
    }

    /**
    *Run a cli command.
    *NOTE: The text utput is supported for JUNOS 11.4 and alter.
    *@param command
    *       the cli command to be executed.
    *@return result of the command,as a String.
    */
    public function runCliCommand() {
        $rpcReply = "";
        $format = "text";
        if(func_num_args() == 2)
            $format = "html";
        $rpc = "<rpc>";
        $rpc.="<command format=\"text\">";
        $rpc.=func_get_arg(0);
        $rpc.="</command>";
        $rpc.="</rpc>";
        $rpc.="]]>]]>\n";
        $rpcReply = $this->getRPCReply($rpc);
        $this->lastRPCReply = $rpcReply;
        trim($rpcReply);
        $xmlreply = $this->convertToXML($rpcReply);
        if (!$xmlreply) {
            echo "RPC-REPLY is an invalid XML\n";
            return null;
        }
        $tags[0] = "output";
        $output = $xmlreply->findValue($tags);
        if ($output != null) 
            return $output;
        return $rpcReply;
    }

    /**
    *Loads the candidate configuration from file,
    *configuration should be in XML format.
    *@param configFilu 
    *       Path name of file containing configuration,in xml format,
    *       ro be loaded.
    *@param loadType
    *       You can choose "merge" or "replace" as the loadType.
    */
    public function loadXMLFile($configFile,$loadType) {
        $configuration = "";
        $file = fopen($configFile,"r");
        if (!$file)
            die ("File not found error");
        while ($line = fgets($file))
            $configuration.=$line;
        fclose($file);
        if ($loadType == null ||(!($loadType == "merge") && !($loadType == "replace")))
            die("'loadType' must be merge|replace");
        $this->loadXMLConfiguration($configuration,$loadType);
    }

    /**
    *Loads the candidate configuration from file,
    *configuration should be in text/tree format.
    *@param configFile
    *      Path name of file containining configuration, in xml format,
    *      to be loaded.
    *@param loadType
    *      You can choose "merge" or "replace" as the loadType.
    */
    public function loadTextFile($configFile,$loadType) {
        $configuration = "";
        $file = fopen($configFile,"r");
        if (!$file)
            die("File not found error");
        while ($line = fgets($file))
            $configuration.=$line;
        fclose($file);
        if ($loadType == null || (!($loadType == "merge") && !($loadType == "replace")))
            die("'loadType' argument must be merge|replace\n");
        $this->loadTextConfiguration($configuration,$loadType);
    }

    /**
    *Loads the candidate configuration from file,
    *configuration should be in set format.
    *NOTE: This method is applicable only for JUNOS release 11.4 and above.
    *@param configFile
    *     Path name of file containing configuration, in set format, 
    *     to be loaded.
    */
    public function loadSetFile($configFile) {
        $configuration = "";
        $file = fopen($configFile,"r");
        if (!$file)
            die("File not found error");
        while ($line = fgets($file))
            $configuration.=$line;
        fclose($file);
        $this->loadSetConfiguration($configuration);
    }

    private function getConfig($target,$configTree) {
        $rpc = "<rpc>";
        $rpc.="<get-config>";
        $rpc.="<source>";
        $rpc.="<".$target."/>";
        $rpc.="</source>";
        $rpc.="<filter type=\"subtree\">";
        $rpc.=$configTree;
        $rpc.="</filter>";
        $rpc.="</get-config>";
        $rpc.="</rpc>";
        $rpc.="]]>]]>\n";
        $rpcReply = $this->getRPCReply($rpc);
        $this->lastRPCReply = $rpcReply;
        return $rpcReply;
    }

    /**
    *etrieve the candidate configuration, or part of the configuration.
    *If no argument is specified, then the
    *configuration is returned for
    *&gt;<configuration$gt;&lt;/configuration&gt;  
    *else 
    *For example, to get the whole configuration, argument should be 
    *&lt;configuration&gt;&lt;/configuration&gt;
    *return configuration data as XML object.
    */
    public function getCandidateConfig() {
        if(func_num_args() == 1)
            return $this->convertToXML($this->getConfig("candidate",func_get_arg(0)));
        return $this->convertToXML($this->getConfig("candidate","<configuration></configuration>"));
    }

    /**
    *Retrieve the running configuration, or part of the configuration.
    *If no argument is specified then 
    *configuration is returned for
    *&lt;configuration&gt;&lt;/configuration&gt;
    *else
    *For example, to get the whole configuration, argument should be 
    *&lt;configuration&gt;&lt;/configuration&gt;
    @return configuration data as XML object.
    */
    public function getRunningConfig() {
        if (func_num_args() ==1)
            return $this->convertToXML($this->getConfig("running",func_get_arg(0)));
        return $this->convertToXML($this->getConfig("running","<configuration></configuration>"));
    }

    /**
    *Loads and commits the candidate configuration, Configuration can be in text/xml/set foramt.
    *@param configFile
    *      Path name of file containing configuration, in text/xml/set format,
    *      to be loaded. For example,
    *"system{
    *    services{
    *        ftp;
    *    }
    *}"
    *will load 'ftp' under the 'systems services' hierarchy.
    *OR
    *&lt;configuration&gt;&lt;system&gt;&lt;serivces&gt;ftp&lt;/services&gt;&lt;/system&gt;&lt;/configuration&gt;
    *will load 'ftp' under the 'systems services' hierarchy.
    *OR
    *"set system services ftp"
    *wull load 'ftp' under the 'systems services' hierarchy.
    *@param loadType
    *     You can choose "merge" or "replace" as the loadType.
    *NOTE : This parameter's value is redundant in case the file contains 
    *configuration in 'set' format.
    */
    public function commitThisConfiguration($configFile,$loadType) {
        $configuration = "";
        $file = fopen($configFile,"r");
        if (!$file)
            die ("File not found");
        while( $line = fgets($file))
            $configuration.=$line;
        trim($configuration);
        fclose($file);
        if ($this->lockConfig()) {
            if ($this->startsWith($configuration,"<"))
                $this->loadXMLConfiguration($configuration,$loadType);
            else if ($this->startsWith($configuration,"set"))
                $this->loadSetConfiguration($configuration);
            else
                $this->loadTextConfiguration($configuration,$loadType);
            $this->commit();
            $this->unlockConfig();
        }
        else
            die ("Unclean lock operation. Cannot proceed further");
    }


    /*
    *Closes the Netconf session
    */
    public function close() {
        $rpc = "<rpc>";
        $rpc.="<close-session/>";
        $rpc.="</rpc>";
        $rpc.="]]>]]>\n";
        $rpcReply = $this->getRPCReply($rpc);
        $this->lastRPCReply = $rpcReply;
        fclose($this->stream);
    }

    private function createHelloRPC(array $capabilities) {
        $helloRPC = "<hello>\n";
        $helloRPC.="<capabilities>\n";
        foreach ($capabilities as $capIter) {
            $helloRPC.="<capability>".$capIter."</capability>\n";
        }
        $helloRPC.="</capabilities>\n";
        $helloRPC.="</hello>\n";
        $helloRPC.="]]>]]>\n";
        return $helloRPC;
    }

    private function getDefaultClientCapabilities() {
        $defaultCap[0] = "urn:ietf:params:xml:ns:netconf:base:1.0";
        $defaultCap[1] = "urn:ietf:params:xml:ns:netconf:base:1.0#candidate";
        $defaultCap[2] = "urn:ietf:params:xml:ns:netconf:base:1.0#confirmed-commit";
        $defaultCap[3] = "urn:ietf:params:xml:ns:netconf:base:1.0#validate";
        $defaultCap[4] = "urn:ietf:params:xml:ns:netconf:base:1.0#url?protocol=http,ftp,file";
        return $defaultCap;
    }

    private function defaultHelloRPC() {
        $defaultCap = $this->getDefaultClientCapabilities();
        return $this->createHelloRPC($defaultCap);
    }
   
}

?>
