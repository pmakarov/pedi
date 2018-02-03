<?php
namespace EDI\X12;

include "Document.php";

class Parser
{

    private const ELEMENT_SEPARATOR_POSITION = 3;
    private const SUBELEMENT_SEPARATOR_POSITION = 104;
    private const SEGMENT_TERMINATOR_POSITION = 105;
    
    
    public static function parse($infile)
    {
        $segments = array();

        if (!$infile) {
            throw new \Exception('No resource or string passed to parse()');
        }

        $documents = array();

        $data = $infile;
        // treat as string.
        if (strcasecmp(substr($data, 0, 3), 'ISA') != 0) {
            throw new \Exception('ISA segment not found in data stream');
        }

        $segment_terminator = substr($data, self::SEGMENT_TERMINATOR_POSITION, 1);
        $element_separator = substr($data, self::ELEMENT_SEPARATOR_POSITION, 1);
        $subelement_separator = substr($data, self::SUBELEMENT_SEPARATOR_POSITION, 1);

        $document = null;
        $raw_segments = explode($segment_terminator, $data);

        $isas = array();
        $current_isa = null;
        $current_gs = null;
        $current_st = null;

        foreach ($raw_segments as $segment) {
            $elements = explode($element_separator, $segment);
            //if given hex separators, we need to scrub the hell out of them
            $elements[0] = trim(strtoupper($elements[0]));
            $elements[0] = preg_replace('/[[:^print:]]/', '', $elements[0]);
            $identifier = $elements[0];
            // only inspect each element if the subelement separator is present in the string
            if (strpos($segment, $subelement_separator) !== false && $identifier != 'ISA') {
                foreach ($elements as &$element) {
                    if (strpos($segment, $subelement_separator) !== false) {
                        $element = explode($subelement_separator, $element);
                    }
                }
                unset($element);
            }

            switch ($identifier) {
                case 'ISA':
                    $current_isa = array('isa' => $elements);
                    break;
                case 'GS':
                    $current_gs = array('gs' => $elements);
                    break;
                case 'ST':
                    $current_st = array('st' => $elements);
                    break;
                case 'SE':
                    assert($current_gs != null, 'GS data structure isset');
                    $current_st['se'] = $elements;
                    if (!isset($current_gs['txn_sets'])) {
                        $current_gs['txn_sets'] = array();
                    }
                    array_push($current_gs['txn_sets'], $current_st);
                    $current_st = null;
                    break;
                case 'GE':
                    assert($current_isa != null, 'ST data structure isset');
                    $current_gs['ge'] = $elements;
                    if (!isset($current_isa['func_groups'])) {
                        $current_isa['func_groups'] = array();
                    }
                    array_push($current_isa['func_groups'], $current_gs);
                    $current_gs = null;
                    break;
                case 'IEA':
                    $current_isa['iea'] = $elements;
                    foreach ($current_isa['func_groups'] as $gs) {
                        foreach ($gs['txn_sets'] as $st) {
                            $segments = array_merge(
                                array(
                                    $current_isa['isa'],
                                    $gs['gs'],
                                    $st['st'],
                                ),
                                $st['segments'],
                                array(
                                    $st['se'],
                                    $gs['ge'],
                                    $current_isa['iea'],
                                )
                            );

                            $document = new Document($segments);
                            array_push($documents, $document);
                        }
                    }
                    break;
                default:
                    if (!isset($current_st['segments'])) {
                        $current_st['segments'] = array();
                    }
                    array_push($current_st['segments'], $elements);
                    break;
            }
        }

        return $documents;
    }

}
