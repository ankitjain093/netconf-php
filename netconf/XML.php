<?php

include('XMLBuilder.php');

/**
 * An <code>XML</code> object represents XML content and provides methods to 
 * manipulate it. 
 * The <code>XML</code> object has an 'active' element, which represents the 
 * hierarchy at which the XML can be manipulated.
 * <p>
 * As an example, one
 * <ol>
 * <li>creates a XMLBuilder object.</li>
 * <li>create a configuration as an XML object.</li>
 * <li>Call the loadXMLConfiguration(XML) method on Device</li>
 * </ol>
 * 
 */
class XML {
    var $activeElement;
    var $ownerDoc;

    public function XML($active,$document) {
        $this->activeElement = $active;
        $this->ownerDoc = $document;
    }

    /**
    *Get the owner Document for the XML object.
    */
    public function getOwnerDocument() {
        return $this->ownerDoc;
    }

    /**
    *Set atirbute for the active element of XML object.
    *@param name
    *      The name of the attribute.
    *@param value
    *      The value of the attribute.
    */
    public function setAttribute($name,$value) {
        $this->activeElement->setAttribute($name,$value);
    }

    /**
    *Set text for the active elment of XML object.
    *@param text
    *        The text value to be set.
    */
    public function setText($text) {
        $firstChild = $this->activeElement->firstChild;
        if ($firstChild == null) {
            $textNode = $this->ownerDoc->createTextNode($text);
            $this->activeElement->appendChild($textNode);
        }
        else if ($firstChild->nodeType != 3) {
            $textNode = $this->domDocument->createNextNode($text);
            $this->activeElement->appendChild($textNode);
        }
        else
            $firstChild->nodeValue = $text;
    }

    /**
    *Sets the attribute ("delete","delete") fpr the active element of 
    *XML object.
    */
    public function junosDelete() {
        $this->activeElement->setAttribute("delete","delete");
    }

    /**
    *Sets the attribute ("active","active") for the active element of
    *XML object.
    */
    public function junosActive() {
        $this->activeElement->setAttribute("active","active");
    }

    /**
    *Sets the attribute ("inactive","inactive") for the active element of 
    *XML object.
    */
    public function junosDeactivate() {
        $this->activeElement->setAttribute("inactive","inactive");
    }

    /**
    *Sets the attribute ("rename") and ("name") for the active element of
    *XML object.
    */
    public function junosRename($toBeRenamed,$newName) {
        $this->activeElement->setAttribute("rename",$toBeRenamed);
        $this->activeElement->setAttribute("name",$newName);
    }

    /**
    *Sets the attribute ("insert") and ("name") for the active eleent of 
    *XML object.
    */
    public function junosInsert($before,$name) {
        $this->activeElement->setAttribute("insert",$before);
        $this->activeElement->setAttribute("name",$name);
    }

    private function is_assoc($arr)
    {
        return (is_array($arr) && count(array_filter(array_keys($arr),'is_string')) == count($arr));
    }

    /**
    *Append an element under the active elment of XML object.
    *This function takes variable no of arguments.
    *<ol>
    *<li> only element: Append an element under the active element 
    *of XML object. The new element now becomes teh active element.</li> 
    *<li>one element and one text : Append an element, with text, under the 
    *active element of XML object. The new element now becomes 
    *the active element.</li> 
    *<li>one element and an arrya of text values : Append multiple elements, 
    *with same name but differnet text under the active element of XML object.</li>
    *<li>An associative array : Append multiple elements with different names 
    *and different text, under the active element of XML object.In this, 
    *each entry contains element name as the key and text value as the 
    *key value.</li>
    *<li>An element and an associative array : Append an element under the 
    *active element of XML object. The new element now vecomes the 
    *active element.Then,append multiple elements wiht different names 
    *and differnet text, under the new active element.</li></ol>
    *@return The modified XML after apending the element.
    */
    public function append() {
        $numOfArgs = func_num_args();
        if ($numOfArgs < 1 && $numOfArgs > 2)
            die("Invalid number of arguments");
        if ($numOfArgs == 2) {
            if (!is_array(func_get_arg(1)) && !$this->is_assoc(func_get_arg(1))) {
                $newElement = $this->ownerDoc->createElement(func_get_arg(0));
                $textNode = $this->ownerDoc->createTextNode(func_get_arg(1));
                $this->activeElement->appendChild($newElement);
                $newElement->appendChild($textNode);
                return new XML($newElement,$this->ownerDoc);
            }
            else if (gettype(func_get_arg(1)) == "array" && !$this->is_assoc(func_get_arg(1))) {
                foreach (func_get_arg(1) as $text) {
                    $newElement = $this->ownerDoc->createElement(func_get_arg(0));
                    $textNode = $this->ownerDoc->createTextNode($text);
                    $this->activeElement->appendChild($newElement);
                    $newElement->appendChild($textNode);
                }
                return null;
            }
            else if ($this->is_assoc(func_get_arg(1))) {
                $newElement = $this->ownerDoc->createElement(func_get_arg(0));
                $this->activeElement->appendChild($newElement);
                $newXML = new XML($newElement,$this->ownerDoc);
                $newXML->append(func_get_arg(1));
                return $newXML;
            }
        }
        else {
            if ($this->is_assoc(func_get_arg(0))) {
                foreach(func_get_arg(0) as $property => $value) {
                    $this->append($property,$value);
                }
            }
            else {
                $newElement = $this->ownerDoc->createElement(func_get_arg(0));
                $this->activeElement->appendChild($newElement);
                return new XML($newElement,$this->ownerDoc);
            }
        }
    }

    /**
    *Add a sibling with the active element of XML object.
    *This function takes variable number of arguments.
    *<ol>
    *<li>An element : Add a sibling element with the active element 
    *of XML object.</li>
    *<li>An element and a text value: Append a sibling element, with text,
    *with the active element of XML object.</li></ol>
    */
    public function addSibling() {
        $numOfArgs = func_num_args();
        if ($numOfArgs < 1 && $numOfArgs> 2)
            die("Invalid number of arguments");
        $newElement = $this->ownerDoc->createElement(func_get_arg(0));
        $parentNode = $this->activeElement->parentNode;
        if ($numOfArgs == 2) {
            $textNode = $this->ownerDoc->createTextNode(func_get_arg(1));
            $newElement->appendChild($textNode);
        }
        $parentNode->appendChild($newElement);
    }

    /**
    *Add multiple siblings
    *This function takes variable number of arguments.
    *<ol>
    *<li>An element and an array of text values : Add multiple sibling elements,
    *with same names but different text, with teh active element of XML object.
    *</li>
    *<li>An associative array : Add multiple siblings with differnet names 
    *and different text, with the active element of XML objcet.In this, 
    *each entry containing element name as the key and text value as the 
    *key cvalue.</li></ol>
    */
    public function addSiblings() {
        $numOfArgs = func_num_args();
        if ($numOfArgs < 1 && $numOfArgs >2)
            die("Invalid number of arguments");
        $parentNode = $this->activeElement->parentNode;
        if ($numOfArgs == 1 && $this->is_assoc(func_get_arg(0))) {
            $inter = $this->activeElement;
            $this->activeElement = $parentNode;
            foreach (func_get_arg(0) as $property => $value)
                 $this->append($property,$value);
            $this->activeElement = $inter;
        }
        else {
            foreach (func_get_arg(1) as $text) {
                $element = $this->ownerDoc->createElement(func_get_arg(0));
                $textNode = $this->ownerDoc->createTextNode($text);
                $element->appendChild($textNode);
                $parentNode->appendChild($element);
            }
        }
    }

    /**
    *Append multiple elements under the active element of XML object, 
    *by specifying the path. The bottomost hierarchy element now becomes 
    *the active element.
    *@param path
    *      The path to be added. For example, to add the hierarchy:
    *     &lt;a&gt;
    *         &lt;b&gt;
    *           &lt;/c&gt;
    *         &lt;/b&gt;
    *       &lt;/a&gt;
    *       The path should be "a/b/c"
    *@return The modified XML.
    */
    public function addPath($path) {
        $elements = explode("/",$path);
        $newElement = null;
        foreach ($elements as $value) {
            $newElement = $this->ownerDoc->createElement($value);
            $this->activeElement->appendChild($newElement);
            $this->activeElement = $newElement;
        }
        return new XML($newElement,$this->ownerDoc);
    }

    /**
    *Get the XML String of the XML object.
    *@return the XML data as a string
    */
    public function toString() {
        $str = $this->ownerDoc->saveXML();
        return $str;
    }

    /**
     * Find the text value of an element.
     * @param list
     *          The String based list of elements which determine the hierarchy.
     *          For example, for the below XML:
     *          &lt;rpc-reply&gt;
     *           &lt;environment-information&gt;
     *            &lt;environment-item&gt;
     *             &lt;name&gt;FPC 0 CPU&lt;/name&gt;
     *              &lt;temperature&gt;
     *          To find out the text value of temperature node, the list
     *          should be- {"environment-information","environment-item",
     *          "name~FPC 0 CPU","temperature"}
     * @return The text value of the element.
    */
    public function findValue($list) {
        $root = $this->ownerDoc->documentElement;
        $nextElement = $root;
        $nextElementFound = false;
        for ($k = 0; $k < sizeof($list); $k++) {
            $nextElementFound = false;
            $nextElementName = $list[$k];
            if (!strpos($nextElementName,"~")) {
                $nextElementList = $root->getElementsByTagName($nextElementName);
                $n2nString = null;
                if ($k < sizeof($list)-1)
                    $n2nString = $list[$k+1];
                if ($n2nString != null && strpos($n2nString,"~")) {
                    $n2nText = substr($n2nString,strpos($n2nString,"~")+1);
                    $n2nElementName = substr($n2nString,0,strpos($n2nString,"~"));
                    for ($i = 0; $i < sizeof($nextElementList); $i++) {
                        $nextElement = $nextElementList->item($i);
                        $n2nElement = $nextElement->getElementsByTagName($n2nElementName)->item(0);
                        $text = $n2nElement->firstChild->nodeValue;
                        trim($text);
                        if ($text != null && $text == $n2nText) {
                            $nextElementFound = true;
                            break;
                        }
                    }
                    if (!$nextElementFound)
                        return null;
                }
                else {
                    $nextElement = $nextElementList->item(0);
                }
            }
            else
                continue;
        }
        if ($nextElement == null) 
            return null;
        $value = $nextElement->firstChild->nodeValue;
        if ($value == null)
            return null;
        return trim($value);
    }

}

?>
