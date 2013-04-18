<?php

/**
 * An <code>XMLBuilder</code> is used to create an XML object.This is useful to 
 * create XML RPC's and configurations.
 * <p>
 * As an example, one
 * <ol>
 * <li>creates a {@link #XMLBuilder() XMLBuilder} object.</li>
 * <li>create an RPC as an XML object.</li>
 * <li>Call the executeRPC(XML) method on Device</li>
 * </ol>
*/
class XMLBuilder {
    var $dom;

    /**
    *Prepares a new <code>XMLBuilder<code/> oobject.
    */
    public function XMLBuilder() {
        $this->dom = new DOMImplementation();
    }

    /**
    *Create a new configuration as an XML object.
    *This function takes multiple no of arguments. And can be one elment
    *two elements or a list of elements.
    *The first argument will be at topmost hierarchy and so on.
    *@return XML object.
    */
    public function createNewConfig() {
        $newelement = "";
        $domdocument = $this->dom->createDocument(null,"configuration");
        $domdocument->formatOutput = true;
        $rootElement = $domdocument->documentElement;
        $root = $rootElement;
        if (is_array(func_get_arg(0))) {
            foreach (func_get_arg(0) as $value) {
                $newelement = $domdocument->createElement($value);
                $rootElement->appendChild($newelement);
                $rootElement = $newelement;
            }
        }
        else {
            $numOfElement = func_num_args();
            $newelement = $domdocument->createElement(func_get_arg(0));
            $rootElement->appendChild($newelement);
            for ($i = 1; $i < $numOfElement; $i++) {
                $element = $newelement;
                $newelement = $domdocument->createElement(func_get_arg($i));
                $newelement = $element->appendChild($newelement);
            }
        }
        return new XML($newelement,$domdocument);
    }
    /**
    *Create a new RPC as an XML object.
    *This function takes multiple no of arguments. And can be one elment
    *two elements or a list of elements.
    *The first argument will be at topmost hierarchy and so on.
    *@return XML object.
    */
    public function createNewRPC() {
        $newelement = "";
        $domdocument = $this->dom->createDocument(null,"rpc");
        $domdocument->formatOutput = true;
        $rootElement = $domdocument->documentElement;
        $root = $rootElement;
        if (is_array(func_get_arg(0))) {
            foreach (func_get_arg(0) as $value) {
                $newelement = $domdocument->createElement($value);
                $rootElement->appendChild($newelement);
                $rootElement = $newelement;
            }
        }
        else {
            $numOfElement = func_num_args();
            $newelement = $domdocument->createElement(func_get_arg(0));
            $rootElement->appendChild($newelement);
            for ($i = 1; $i < $numOfElement; $i++) {
                $element = $newelement;
                $newelement = $domdocument->createElement(func_get_arg($i));
                $newelement = $element->appendChild($newelement);
            }
        }
        return new XML($newelement,$domdocument);
    }
    /**
    *Create a new XML as an XML object.
    *This function takes multiple no of arguments. And can be one elment
    *two elements or a list of elements.
    *The first argument will be at topmost hierarchy and so on.
    *@return XML object.
    */
    public function createNewXML() {
        $newelement = "";
        $domdocument->formatOutput = true;
        if (is_array(func_get_arg(0))) {
            $arguments = func_get_arg(0);
            $domdocument = $this->dom->createDocument(null,$arguments[0]);
            $rootElement = $domdocument->documentElement;
            $root = $rootElement;
            for ($i = 1; $i < sizeof($arguments); $i++) {
                $newelement = $domdocument->createElement($arguments[$i]);
                $rootElement->appendChild($newelement);
                $rootElement = $newelement;
            }
        }
        else {
            $domdocument = $this->dom->createDocument(null,func_get_arg(0));
            $rootElement = $domdocument->documentElement;
            $numOfElement = func_num_args();
            $newelement = $domdocument->createElement(func_get_arg(1));
            $rootElement->appendChild($newelement);
            for ($i =2 ; $i < $numOfElement; $i++) {
                $element = $newelement;
                $newelement = $domdocument->createElement(func_get_arg($i));
                $newelement = $element->appendChild($newelement);
            }
        }
        return new XML($newelement,$domdocument);
    }


}

?>
