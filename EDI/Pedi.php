<?php

namespace EDI;

require_once('EDI/X12/Parser.php');
require_once('EDI/X12/SchemaTree.php');

use EDI\X12\Parser;
use EDI\X12\SchemaTree;

class Pedi
{
    // holds complete edi documents from parser
    private $documents = [];
    // indicates whether to aggregate warnings and errors 
    // about an edi document
    private $validate = false;
    // sets a default edi type as ASC X12 (instead of EDIFACT, TRADACOM, or 
    // some proprietary XML document)
    private $edi_type = "X12";
    // holds an individual edi model to aid in parsing and validation
    private $edi_schema_model;

    // tree (as an array) data structure to hold edi data nodes
    // for search, update, and display purposes
    private $tree = [];
    // hold a reference to the previous edi node added to tree
    private $prev = null;
    // array hash to store calculate max/min usage counts for edi elements
    private $counter = [];
    
    

    public function __construct($params = null)
    {

        if (count($params) > 0) {
            foreach ($params as $key => $value) {
                $this->$key = $value;
            }
        }

    }

    public function read($path)
    {

        $this->load($path);

    }

    public function documents()
    {
        return $this->documents;
    }

    public function load($path)
    {
        try {

            $input_edi = file_get_contents($path);
            $this->documents = Parser::parse($input_edi);

            $this->doSomething();

        } catch (Exception $e) {

            echo $e->getMessage();

        }
    }

    public function doSomething()
    {
        /* Loop the imported EDI documents */
        foreach ($this->documents as $document) {
            $path = "";

            $schema = ($this->schema != null) ? $this->schema : $this->getDocumentSchemaFromX12($document);
            // look for a schema file at path + schema + .json
            $schema_path = __DIR__ . "/X12/schemas/" . $schema . ".json";
            // TODO: create grammar lookup object to match user-provided
            // schema against schemas registered in the EDI/X12/schemas folder

            // initialize x12 schema validator tree
            $this->edi_schema_tree = new SchemaTree($schema_path);
            // get handle to root of tree for use in x12 processing
            $this->tree_root = $this->edi_schema_tree->root();
            
            if($this->debug) {
                print "/" . str_repeat("*", 20) . " DEBUG EDI DOCUMENT PARSE" . str_repeat("*", 20) . "/\n";
            }

            // we start at 2 so we skip ISA and GS segments
            for ($i = 2; $i < count($document->segments); $i++) {

                $current_element = $document->segments[$i][0];
                // terminate loop if we've made it past ST/SE -> GE
                if ($current_element === "GE") {
                    break;
                }

                // update path so we can use it to query the schema tree
                $path .= $current_element . "/";
                //print "Try to find a node @ path: " . $path . "\n";
                $node = $this->findNodeByPath($this->tree_root, $path);
                
                if ($node != null) {
                    if($this->debug) {
                        print $i-1 . ". Found Node  " . $path . "\n";
                        print "\t" . $path ." can be used: " . $node['max'] . " time(s)\n";
                    }
                    
                    // need to count occurrences for basic validation
                    if(!array_key_exists($path, $this->counter)) {

                        $this->counter[$path] = 1;

                    } 
                    else {
                        //  if($path = "ST/CTT/")
                        $this->counter[$path]++;
                        
                    }
                    
                    //print_r($node);

                    // TODO: change into actual class
                    // create new node
                    $nod = new \stdClass;
                    $nod->name = $document->segments[$i][0];
                    $nod->data = array_slice($document->segments[$i], 1);
                    $nod->children = [];
            
                    // set prev node = $tree on start
                    // we add the ST element node as tree root, 
                    // set prev = st
                    
                    // * create a pointer to the parent of a node
                    if (count($this->tree) < 1) {

                        array_push($this->tree, $nod);

                    } else {
                        // if( $this->prev == NULL) {
                        //     print "was null\n";
                        //     array_push($this->tree[0]->children, $nod);
                        // } else {

                        //     array_push($this->prev->children, $nod);

                        // }

                        array_push($this->prev->children, $nod);
                        
                    }

                    $nod->parent = $this->prev;
                    $this->prev = $nod;
                    
                    //addDataToNodeByPath($tree_root, $path, array_splice($document->segments[$i], 1));
                    //array_push($node['data'], array_splice($document->segments[$i], 1));

                    //look ahead:
                    if ($i + 1 < count($document->segments)) {

                        if( $this->debug) {
                            print "\t[Looking ahead in file] next segment: " . $document->segments[$i + 1][0] . "\n";
                        }

                        $next = $document->segments[$i + 1][0];
                        if ($this->hasChild($node, $next)) {

                            if( $this->debug) {
                                print "\t" . $path . " has a " . $next . " as a child.\n";
                            }

                            continue;
                        } else {
                            //pop current node from path
                            $path = str_replace($document->segments[$i][0] . "/", '', $path);
                            if( $this->debug) {
                                print "\t" . $next . " is NOT a child element of node: " . $document->segments[$i][0] . "\n\tGo back a level to: " . $path . "\n";
                            }
                            $this->prev = $this->prev->parent;
                        }
                    }

                } 
                else {
                    
                    $tmp = explode('/', $path);
                    $tmp2 = array_splice($tmp, -3);
                    $path = implode('/', $tmp) . "/";

                    if( $this->debug) {
                        print "\t" . $current_element . " is NOT a child node! So we POP back a level to: " . $path . "\n";
                    }

                    $this->prev = $this->prev->parent;
                    $i--;
                    
                    // if ($this->prev) {
                    //     $this->prev = $this->prev->parent;
                    //     $i--;
                    // } else {
                    //     print "we have to skip this bad element - its out of place: " . $current_element . "\n";
                    //     //$current_element = $document->segments[$i+1][0];
                    //     $path = "ST/";
                    //     // print $current_element . " fuck " . $path . "\n";
                    //     //continue;
                    // }

                }
                
            }

            if($this->debug) {
                print "/" . str_repeat("*", 20) . " END EDI DOCUMENT PARSE" . str_repeat("*", 20) . "/\n\n";
            }

            //print_r($this->tree_root);
            if( $this->debug) {
                print "\n\n/" . str_repeat("*",20) . "/";
                print " Printing EDI Schema ";
                print "/" . str_repeat("*",20) . "/\n";
                print $this->edi_schema_tree;
                print "/" . str_repeat("*",20) . "/";
                print " End of EDI Schema ";
                print "/" . str_repeat("*",20) . "/\n\n\n";

                //print_r($this->counter);
            }
        }
    }

    public function getDocumentSchemaFromX12($document)
    {
        //ST01
        $schema = $document->segments[2][1];
        // GS07
        $version = $document->segments[1][8];

    }

    public function findNodeByPath(&$tree_node, $path, &$skip = false)
    {
        $path = rtrim($path, '/');
        if ($tree_node['path'] === $path || $tree_node['path'] === $path . '/') {
            if ($skip == false) {
                return $tree_node;
            } else {
                $skip = false;
            }
        }

        if (array_key_exists('children', $tree_node)) {
            foreach ($tree_node['children'] as $node => &$field) {
                $found = $this->findNodeByPath($field, $path, $skip);
                if ($found) {
                    return $found;
                }
            }
        }
    }

    public function hasChild($node, $child_name)
    {

        if (array_key_exists('children', $node)) {
            for ($i = 0; $i < count($node['children']); $i++) {
                if ($node['children'][$i]['name'] === $child_name) {
                    //print "max count: " . $node['children'][$i]['max'] . "\n";
                    return true;
                }
            }
        }

        return false;
    }

    public function find($path)
    {
        $arr = explode("/", $path);
        $elements = [];
        $elements = $this->find_node($elements, $this->tree, $arr, 0);

        return $elements;
    }

    public function find_node(&$arr, $children, &$path, $count)
    {
        //print "Gonna find shit: " . $path[$count] . "\n";
        //print $count + 1 . " ? " . count($path) . "\n";    
    
    
        //print $path[$count - 1] . " has child count of : " . count($children). "\n";
        // foreach($children as $child) {
        //     print $child->name . "\n";
        // }
        for ($i = 0; $i < count($children); $i++) {
            //print $i . "\n";
            //print $children[$i]->name . " == " .  $path[$count] . "\n";
            if ($children[$i]->name == $path[$count]) {

                if ($count + 1 == count($path)) {
                    // push shit to return array
                    //print count($path) . "... looks like we are at end of path!\n";
                    array_push($arr, $children[$i]);

                } else {
                    //print $count +1 . " is < " . count($path) .  "\n";
                    if (count($children[$i]->children) != 0) {
                        $count++;
                        $this->find_node($arr, $children[$i]->children, $path, $count);
                        // ! when we return from our deeper recursion loop
                        // decrement the path to indicate that we've walked
                        // back up the path one level
                        $count--;
                    }


                }

            }


        }

        return $arr;
        
        // if ( $count + 1 === count($path)) {
        //     //print_r($arr);
        // }


    }

    public function export()
    {

        foreach ($this->documents as $document) {
            print $document;
        }

    }

    public function debug_tree() {
        print_r($this->tree);
    }


}