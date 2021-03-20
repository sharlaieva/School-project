<?php
ini_set('display_errors', 'stderr');

$opcode_count = 1;
$headerFound = false;
$labelStack = array();
$labelsCount = 0;

/**
 * Pridani korenoveho prvku programu
 */

$program = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'.'<program> </program>');
$program->addAttribute('language', 'IPPcode21');

/**
 * Funkce odstrani ze radku komentar pokud on obsahuje #
 */

function cutComments($string) {
    $hashPosition = strpos($string, "#");
    if($hashPosition != False)
        $string = substr($string, 0, $hashPosition);
    return $string;

}

/**
 * Funkce zpracuje argumenty prikazoveho radku
 */
function cmdArguments($arg_cnt, $arguments) {
    if ($arg_cnt == 2)
    {
        if (strcmp($arguments[1], "--help") == 0 || strcmp($arguments[1], "-h") == 0)
        {
            echo "Program nacte zdrojovy kod v jazyce IPPcode21 ze standartniho vstupu a vypise na stdout XML reprezentaci programu\n";
		    exit(0);
        }
        else
        {
            exit(10);
        }
    }
    if($arg_cnt > 2)        // Pokud argumentu je vice nez 2
        exit(10);
        
}

/**
 * Funkce vezme argument a vrati jeho typ a hodnotu jako pole.
 * Pripadne zkontroluje spravnost nazvu prommennych, navesti nebo literalu.
 */

function checkType($argument) {
    $returnArray = [];
    if(strpos($argument, '@')!== false){
        $arr = explode('@', $argument,2);
        if ($arr[0] == "GF" || $arr[0] == "TF" || $arr[0] == "LF"){
            array_push($returnArray, "var");
            if(preg_match("/^[A-za-z_\$\&\%\*\!\?\-][a-zA-Z_0-9_\$\&\%\*\!\?\-]*$/", $arr[1])){
                array_push($returnArray, $argument);
                return($returnArray);
            }
        }

        elseif($arr[0] == "int"){
            if(!preg_match("/^[\+\-0-9][0-9]*$/", $arr[1]))
                exit(23);
            array_push($returnArray, "int");
            array_push($returnArray, $arr[1]);
            return($returnArray);
        }
        elseif($arr[0] == "string") {
            if(!preg_match("/^([^\\\\]|\\\\\d\d\d)*$/", $arr[1]))
                exit(23);
            array_push($returnArray, "string");
            array_push($returnArray, $arr[1]);
            return($returnArray);
        }
        elseif($arr[0] == "bool"){
            if(strtolower($arr[1]) ==="true" || strtolower($arr[1])=== "false") {
                array_push($returnArray, "bool");
                array_push($returnArray, $arr[1]);
                return($returnArray);
            }
            else
                exit(23);
        }

        elseif($arr[0] == "nil"){
            array_push($returnArray, "nil");
            if($arr[1] ==="nil") {
                array_push($returnArray, $arr[1]);
            }
            else 
                exit(23);
            return($returnArray);
        }
        
        else
            exit(23);
        
    }
    else{
        if ($argument=="int" || $argument=="string" || $argument=="bool" || $argument=="nil") {
            array_push($returnArray, "type");
            array_push($returnArray, $argument);
            return $returnArray;
        }
        if(preg_match("/^[A-za-z_\$\&\%\*\!\?\-][a-zA-Z_0-9_\$\&\%\*\!\?]*$/", $argument)){
            array_push($returnArray, "label");
            array_push($returnArray, $argument);
            return $returnArray;
        }
        else
            exit(23);
        
    }
        
}
/**
 * Funkce pridava prvky do XML stromu. 
 */

function addToXML($arr) {
    global $opcode_count;
    global $program;
    $instruction[$opcode_count] = $program->addChild('instruction');
    $instruction[$opcode_count]->addAttribute('order', $opcode_count);
    $instruction[$opcode_count]->addAttribute('opcode', $arr[0]);
    if(count($arr)>1){
        for ($i =1; $i<=count($arr)-1; $i++)
        {
            $argument[$i-2] = $instruction[$opcode_count]->addChild("arg".($i), htmlspecialchars($arr[$i][1]));
            $type = $arr[$i][0];
            $argument[$i-2]->addAttribute('type', $type);
        }
    } 
    $opcode_count++;
}

/**
 * Funkce zkontroluje typy jednotlivych argumentu instrukce.
 * Pokud jsou spravne zavola addToXML() funkce.
 */

function checkArgumentsTypes($instructionArray) {
    $instructionArray[0] = strtoupper($instructionArray[0]);
    switch($instructionArray[0]){

    case "MOVE":
    case "TYPE":
    case "NOT":
    case "INT2CHAR":

        $arr1 = checkType($instructionArray[1]);
        if($arr1[0]!== "var")
            exit(23);
        $arrToXML = array();
        array_unshift($arrToXML, $instructionArray[0]);
        array_push($arrToXML, $arr1);
        $arr2 = checkType($instructionArray[2]);
        if($arr2[0] == "label" || $arr2[0] == "type")
            exit(23);
        array_push($arrToXML, $arr2);
        addToXML($arrToXML);
        break;
    
    case "CREATEFRAME":
    case "PUSHFRAME":
    case "POPFRAME":
    case "BREAK":
    case "RETURN":

        addToXML($instructionArray);
        break;
    
    case "DEFVAR":
    case "POPS":

        $arr = checkType($instructionArray[1]);
        if($arr[0]!== "var")
            exit(23);
        $arrToXML = array();
        array_unshift($arrToXML, $instructionArray[0]);
        array_push($arrToXML, $arr);
        addToXML($arrToXML);
        break;

    case "CALL":
    case "LABEL":
    case "JUMP":
        global $labelStack;
        $arr = checkType($instructionArray[1]);
        if($arr[0]== "label" || $arr[0]== "type"){
            $arr[0] = "label";
        }
        else
            exit(23);
        if(!in_array($arr[1], $labelStack)) {
            array_push($labelStack, $arr[1]);
            global $labelsCount;
            $labelsCount++;
        }
        else
            exit(23);
        $arrToXML = array();
        array_unshift($arrToXML, $instructionArray[0]);
        array_push($arrToXML, $arr);
        addToXML($arrToXML);
        break;
    
    case "PUSHS":
    case "DPRINT":
    case "WRITE":
    case "EXIT":
        $arrToXML = array();
        $arr1 = checkType($instructionArray[1]);
        array_unshift($arrToXML, $instructionArray[0]);
        array_push($arrToXML, $arr1);
        if($arr1[0] == "label" || $arr1[0] == "type")
        exit(23);
        addToXML($arrToXML);
        break;
        
    case "ADD":
    case "SUB":
    case "MUL":
    case "IDIV":
    case "LT":
    case "GT":
    case "EQ":
    case "AND":
    case "OR":
    case "STRI2INT":
    case "GETCHAR":
    case "CONCAT":
    case "SETCHAR":
        $arrToXML = array();
        $arr1 = checkType($instructionArray[1]);
        if($arr1[0]!=="var")
            exit(23);
        $arr2 = checkType($instructionArray[2]);
        if($arr2[0] =="label" || $arr2[0] =="type")
            exit(23);
        $arr3 = checkType($instructionArray[3]);
        if($arr3[0] =="label" || $arr3[0] =="type")
            exit(23);
        array_push($arrToXML, $arr1);
        array_push($arrToXML, $arr2);
        array_push($arrToXML, $arr3);
        array_unshift($arrToXML, $instructionArray[0]);
        addToXML($arrToXML);
        break;


    case "READ":
        $arrToXML = array();
        $arr1 = checkType($instructionArray[1]);
        if($arr1[0]!=="var")
            exit(23);
        $arr2 = checkType($instructionArray[2]);
        if($arr2[0] == "type") {
            if ($arr2[1] != "int" && $arr2[1] != "string" && $arr2[1] != "bool")
                exit(23);
        }
        else
            exit(23);
     
        array_push($arrToXML, $arr1);
        array_push($arrToXML, $arr2);
        array_unshift($arrToXML, $instructionArray[0]);
        addToXML($arrToXML);
        break;

    case "STRLEN":

        $arrToXML = array();
        $arr1 = checkType($instructionArray[1]);
        if($arr1[0]!=="var")
            exit(23);
        $arr2 = checkType($instructionArray[2]);
        if($arr2[0] == "label" || $arr2[0] =="type")
            exit(23);
     
        array_push($arrToXML, $arr1);
        array_push($arrToXML, $arr2);
        array_unshift($arrToXML, $instructionArray[0]);
        addToXML($arrToXML);
        break;


    case "JUMPIFEQ":
    case "JUMPIFNEQ":
        $arrToXML = array();
        $arr1 = checkType($instructionArray[1]);
        if($arr1[0]== "label" || $arr1[0]== "type")
            $arr1[0] = "label";
        else
            exit(23);
        $arr2 = checkType($instructionArray[2]);
        $arr3 = checkType($instructionArray[3]);
        
        if($arr2[0] == "label" || $arr2[0] =="type")
            exit(23);
        if($arr3[0] == "label" || $arr3[0] =="type")
            exit(23);
        array_push($arrToXML, $arr1);
        array_push($arrToXML, $arr2);
        array_push($arrToXML, $arr3);
        array_unshift($arrToXML, $instructionArray[0]);
        addToXML($arrToXML);
        break;

    default:
        exit(22);
}
}

/**
 * Funkce kontroluje spravnost jmena (opcode) a poctu argumentu instrukce.
 */
function checkCountOfArguments($array) {   
    if(!empty($array))
    {
        $opcode = strtoupper($array[0]);
        $numberOfElements = count($array)-1;

        $zero_argument = array(
            "CREATEFRAME", "PUSHFRAME", "POPFRAME", "RETURN", "BREAK"
        );
        $one_argument = array(
            "DEFVAR", "CALL", "PUSHS", "POPS", "WRITE", "LABEL", "JUMP", "EXIT", "DPRINT"
        );
        $two_arguments = array(
            "MOVE", "INT2CHAR", "READ", "STRLEN", "TYPE", "NOT"
        );
        $three_arguments = array(
            "ADD", "SUB", "MUL", "IDIV", "LT", "GT", "EQ", "AND", "OR", "STRI2INT", "CONCAT", "GETCHAR", "SETCHAR", "JUMPIFEQ", "JUMPIFNEQ"
        );

        if(!empty($opcode))
        {    
            // Neznamy opcode instrukce
            if (!in_array($opcode, $zero_argument) and !in_array($opcode, $one_argument) and !in_array($opcode, $two_arguments) and !in_array($opcode, $three_arguments))
                exit(22);
            
        };
    }
    // Kontrola poctu argumentu instrukce
    switch ($numberOfElements)
    {
        case 0:
            if (!in_array($opcode, $zero_argument))
                exit(23);
        break;
        
        case 1:
            if (!in_array($opcode, $one_argument))
                exit(23);
        break;
        
        case 2:
            if (!in_array($opcode, $two_arguments))
                exit(23);
        break;
        
        case 3:
        if (!in_array($opcode, $three_arguments))
            exit(23);
        break;
    // >3 argumentu    
        default:
                exit(23);
        break;
    }
   
}

/**
 * Prvni funkce u zpracovani instrukci.
 * Funkce rozdeli vstupni radek na slova, potom zavola dalsi funkci lexikalni a syntakticku analyzu. 
 */

function readInstructions($instructionArray) {
    $instruction = explode(' ', $instructionArray);
    checkCountOfArguments($instruction);
    checkArgumentsTypes($instruction);
}

/**
 * Pocatecny bod programu.
 * Nejprve se vola funkce ktera kontroluje argumenty prikazoveho radku.
 * Potom se nacitaji radky programu v IPPcode21.
 */

cmdArguments($argc, $argv);  

$stdin = fopen('php://stdin', 'r');
if(!$stdin) {
    exit(11);
}
while(($string = fgets(STDIN)) !== FALSE) {
    global $headerFound;
    $string = trim($string);
    // pokud radek obsahuje jenom komentar
    if(empty($string) || $string[0] == "#")
        continue;
    $string = cutComments($string);
    $string = trim($string);
    if(!$headerFound)
    if (strcmp($string,".IPPcode21") !== 0) {
        exit(21);
    }
    else {
        $headerFound = true;
        continue;
    }
    // nahrazujeme pripadne bile znaky jednou mezerou
    $string = preg_replace('/\s+/', ' ', $string);
    readInstructions($string);
    
}

/**
 * Ukladame vyslednou XML reprezentace.
 */

$dom = new DOMDocument("1.0");
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->loadXML($program->asXML());
fclose(STDIN); 
try{
    $dom->save("php://stdout");
}
catch (exception $e) {
    exit(12);
}

exit(0);