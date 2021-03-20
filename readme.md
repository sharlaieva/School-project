# Analyzátor kódu v IPPcode21 (parse.php)
Skript načte ze STDIN kód v jazyce IPPcode21, zkontroluje lexikální a syntaktickou správnost programu a vypíše na STDOUT jeho XML reprezentaci.
## Parametry
Skript podporuje parametr ``--help`` (nebo krátký ``-h``) pro zobrazení nápovědy.

## Implementace
Program načíta jednotlivé řádky kódu ze vstupního souboru (STDIN) a nejprve odstraňuje komentáře a prázdné řádky, nahradí připadný libovolný počet bílých znaků jednou mezerou pomocí vhodného regexu a bude hledat hlavičku. Pro kontrolu zda hlavička už byla nalezena používá globalní promennou která se mění pouze jednou. Pokud hlavičku najde, pokračuje zpracováním řádků, které teď obsahují jenom instrukce.
Nejprve volá funkce ``readInstructions``. Tato funkce vezme načtený řádek a v prvním kroku rozdělí ho na jednotlivá slova, potom zavolá funkce ``checkCountOfArguments`` která zkontroluje správnost instrukce - její název upravený funkci ``strtoupper`` a počet argumentů. Pokud tato funkce neskončí příslušným chybovým návratovým kódem, zavolá se funkce ``checkArgumentsTypes``.
Funkce ``checkArgumentsTypes`` pro jednotlivou instrukce zjišťuje typ každého argumentu voláním funkce ``checkType``. Funkce ``checkType`` zároveň kontroluje i omezení jmén promenných a literálů. Funkce vrací typ a hodnotu argumentu jako pole. 
Pokud typy argumentů jsou správné přidává je do XML stromu voláním funkce ``addToXML``. Tato fukce přidává ke dřive vytvořenému kořenovému prvku ``program`` další následníky  pomocí funkcí jazyka PHP ``addChild`` a ``addAttribute``. Problematické znaky v XML převádí vestavěná PHP funkce ``htmlspecialchars``.

Po takovém zpracování poslední instrukce bude vygenerována výsledná XML reprezentace. Tato reprezentace bude uložená jako DOMDocument pomocí ``loadXML`` a předána na standardní výstup.

## Spuštění
``$ {{php}} parse.php [přip. parametr --help] < {{vstupní soubour obsahující kód v jazyce IPPcode21}} > {{výstupní soubor}}``
## Soubory
* parse.php - analyzátor.
* readme.md - dokumentace (tento soubor).
