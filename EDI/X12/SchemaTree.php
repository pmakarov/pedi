<?php
namespace EDI\X12;

class SchemaTree
{

    private $root;

    public function __construct($schema = null)
    {
        if (!$schema) {

            throw new Exception('No X12 Schema provided to constructur()');

        } else {

            if ($this->import($schema)) {

                $this->init();

            }
        }
    }

    private function import($schema)
    {
        $this->root = json_decode(file_get_contents($schema), true);

        return $this->root;
    }

    public function root()
    {
        return $this->root[0];
    }

    private function init()
    {
        $this->initialize_schema_tree($this->root, 0, "");
    }

    protected function initialize_schema_tree(&$arr, $lvl, $path)
    {

        foreach ($arr as $value => &$field) {
            if (array_key_exists('children', $field) && is_array($field['children'])) {
                $field['data'] = array();
                $field['depth'] = $lvl;
                $path .= $field['name'] . '/';

                $ars = array_filter(explode("/", $path));

                $ars[$lvl] = $field['name'];

                if (count($ars) > $lvl + 1) {
                    $ars = array_slice($ars, 0, $lvl + 1);
                }

                $path = implode('/', $ars) . '/';

                $field['path'] = $path;
                $field['count'] = 0;

                $this->initialize_schema_tree($field['children'], $lvl + 1, $path);

            } else {
                $field['data'] = array();
                $field['depth'] = $lvl;
                $ars = array_filter(explode("/", $path));

                $ars[$lvl] = $field['name'];

                $path = implode('/', $ars);

                $field['path'] = $path;
                $field['count'] = 0;
            }
        }
    }

    /* printable edi schema tree */
    public function __toString()
    {
        $str = '';
        $this->print_tree($this->root, $str);

        return $str;
    }
    
    /* Print Tree: traverse tree recursively and build output string
     * params: Array root, String return string
     * returns nothing. Note: use of pass by reference
     */
    protected function print_tree($arr, &$str)
    {
        foreach ($arr as $value) {
            if (array_key_exists('children', $value) && is_array($value['children'])) {

                $str .= str_repeat(
                    "   ",
                    $value['depth']
                ) . '[' .
                    $value['name'] . ' min:"' .
                    $value['min'] . '" max:"' .
                    $value['max'] . '" count:"' .
                    $value['count'] . '" @ path:"' .
                    $value['path'] . '"]' . "\n";

                $this->print_tree($value['children'], $str);

            } else {

                $str .= str_repeat(
                    "   ",
                    $value['depth']
                ) . '[' .
                    $value['name'] . ' min:"' .
                    $value['min'] . '" max:"' .
                    $value['max'] . '" count:"' .
                    $value['count'] . '" @ path:"' .
                    $value['path'] . '"]' . "\n";
            }
        }
    }
}