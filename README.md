# check urbis tool

Install PHP first

## Q/A checks based on adddr:street vs name

For nodes/ways containing : addr:street and
highways having a name

 - Checks for flipped naming schemes
 - small spelling mistakes
 - single streetnames in highways
 - non-matching streets vs addressed objects

Specifically made for Brussels

## Usage

You can run this tool on the cli like this:

    Usage:
    -a/--auto : Get all data automatically from Overpass API, will unset file option (not fully implemented yet)
    -o/--osm <value> : The name of the OSM file parse the XML from.
    -c/--changefile <value> : The name of the output diff file (with corrections, not fully implemented yet, harder to do correct)
    -t/--format <value> : The name of the output extension (json, geojson, osm) not implemented since this concerns the changefile.

## Set verbose to 1, 2 or 3 for meaningfull information

1. output the core errors 
2. output the known issues too, like flipped streetnames and spelling errors  (this is the one you want)
3. Display matches as well and a lot more verbose debug information
4. and more .. -> vatos locos mode

## Run it

You can just run it on a .osm file you are editing.  Preferably you could use an overpass query to download a limited set but it will work on JOSM downloads as well if the parser doesn't break on something weird

### overpass qry

http://overpass-turbo.eu/s/iDu

    <!--
    Looks for streets and ways with dash in either addr:street or the name , chances are it's an NL/FR value
    They need to have space in front and after , if not it's probably a dash in the streetname itself.
    -->
    {{key=addr:street}}
    <osm-script output="xml">
    <union>
    <query type="way">
        <has-kv k="{{key}}" regv=" - "/>
        <bbox-query {{bbox}}/>
    </query>
    <query type="node">
        <has-kv k="{{key}}" regv=" - "/>
        <bbox-query {{bbox}}/>
    </query>
    <query type="way">
        <has-kv k="name" regv=" - "/>
        <has-kv k="highway"/>
        <bbox-query {{bbox}}/>
    </query>
    </union>
    <print mode="meta"/>
    <recurse type="down"/>
    <print mode="meta"/>
    </osm-script>fg

Open with JOSM and save the osm, you can validate while editing in JOSM , run the validator
    glenn@slicky:~/urbis/qa$ ./urbis_brussel.php -o addresses_brussel.osm

## Output results (edited for reading)

    info - Found lowercase match (case problem) 'Rue de Ligne - de Lignestraat' [id:2485390614] vs. 'Rue de Ligne - De Lignestraat' [id:15064614]. (Fix the spelling)
    info - Found lowercase match (case problem) 'Rue de Ligne - de Lignestraat' [id:2485390625] vs. 'Rue de Ligne - De Lignestraat' [id:15064614]. (Fix the spelling)
    info - Found deep scan match 'Treurenberg - Treurenberg' [id:2485390643] vs. 'Treurenberg' [id:8511798]. (Fix the single name of the street)
    info - Found deep scan match 'Treurenberg - Treurenberg' [id:2485390650] vs. 'Treurenberg' [id:8511798]. (Fix the single name of the street)
    info - Found deep scan match 'Treurenberg - Treurenberg' [id:2485390659] vs. 'Treurenberg' [id:8511798]. (Fix the single name of the street)
    info - Found deep scan match 'Treurenberg - Treurenberg' [id:2485390668] vs. 'Treurenberg' [id:8511798]. (Fix the single name of the street)
    info - Found deep scan match 'Treurenberg - Treurenberg' [id:2485390673] vs. 'Treurenberg' [id:8511798]. (Fix the single name of the street)
    info - Found deep scan match 'Treurenberg - Treurenberg' [id:2485390679] vs. 'Treurenberg' [id:8511798]. (Fix the single name of the street)
    info - Found deep scan match 'Treurenberg - Treurenberg' [id:2485390684] vs. 'Treurenberg' [id:8511798]. (Fix the single name of the street)
    info - Found deep scan match 'Treurenberg - Treurenberg' [id:2485390690] vs. 'Treurenberg' [id:8511798]. (Fix the single name of the street)
    info - Found deep scan match 'Treurenberg - Treurenberg' [id:2485390695] vs. 'Treurenberg' [id:8511798]. (Fix the single name of the street)
    info - Found deep scan match 'Treurenberg - Treurenberg' [id:2485390707] vs. 'Treurenberg' [id:8511798]. (Fix the single name of the street)
    info - Found lowercase match (case problem) 'Rue de Ligne - de Lignestraat' [id:2485390713] vs. 'Rue de Ligne - De Lignestraat' [id:15064614]. (Fix the spelling)
    info - Found lowercase match (case problem) 'Rue de Ligne - de Lignestraat' [id:2485390727] vs. 'Rue de Ligne - De Lignestraat' [id:15064614]. (Fix the spelling)
    info - Found deep scan match 'Arenbergstraat - Rue d'Arenberg' [id:3118681438] vs. 'Rue d'Arenberg - Arenbergstraat' [id:8511786]. (Fix the order)
    info - Found deep scan match 'Arenbergstraat - Rue d'Arenberg' [id:3118681502] vs. 'Rue d'Arenberg - Arenbergstraat' [id:8511786]. (Fix the order)
    info - Found deep scan match 'Arenbergstraat - Rue d'Arenberg' [id:3118681504] vs. 'Rue d'Arenberg - Arenbergstraat' [id:8511786]. (Fix the order)
    info - Found deep scan match 'Arenbergstraat - Rue d'Arenberg' [id:3118681505] vs. 'Rue d'Arenberg - Arenbergstraat' [id:8511786]. (Fix the order)
    info - Found deep scan match 'Arenbergstraat - Rue d'Arenberg' [id:3118681506] vs. 'Rue d'Arenberg - Arenbergstraat' [id:8511786]. (Fix the order)
    info - Found deep scan match 'Arenbergstraat - Rue d'Arenberg' [id:3118693279] vs. 'Rue d'Arenberg - Arenbergstraat' [id:8511786]. (Fix the order)
    info - Found deep scan match 'Arenbergstraat - Rue d'Arenberg' [id:3118693325] vs. 'Rue d'Arenberg - Arenbergstraat' [id:8511786]. (Fix the order)
    info - Found deep scan match 'Arenbergstraat - Rue d'Arenberg' [id:3118694154] vs. 'Rue d'Arenberg - Arenbergstraat' [id:8511786]. (Fix the order)
    info - Found deep scan match 'Arenbergstraat - Rue d'Arenberg' [id:3118694216] vs. 'Rue d'Arenberg - Arenbergstraat' [id:8511786]. (Fix the order)
    info - Found deep scan match (levenshtein) 'Rue de l'Ecuyer - Schildknaapsstraat' [id:3118694224] vs. 'Rue de l'Écuyer - Schildknaapsstraat' [id:33739362]. (Fix the minor spell differences)
    core - Osm_id: 3261888514 - Missing matching street name Rue des Ateliers - Werkhuizenstraat.
    core - Verify this in JOSM using search on id:3261888514 - This address might be very wrong  Rue des Ateliers - Werkhuizenstraat.
    info - Found lowercase match (case problem) 'Rue T'Serclaes - T'Serclaesstraat' [id:3506580845] vs. 'Rue t'Serclaes - t'Serclaesstraat' [id:23093742]. (Fix the spelling)
    info - Found lowercase match (case problem) 'Rue T'Serclaes - T'Serclaesstraat' [id:3506580892] vs. 'Rue t'Serclaes - t'Serclaesstraat' [id:23093742]. (Fix the spelling)
    info - Found deep scan match (levenshtein) 'Rue de l'Ecuyer - Schildknaapsstraat' [id:3506621373] vs. 'Rue de l'Écuyer - Schildknaapsstraat' [id:33739362]. (Fix the minor spell differences)
    info - Found deep scan match (levenshtein) 'Rue de l'Ecuyer - Schildknaapsstraat' [id:3506621381] vs. 'Rue de l'Écuyer - Schildknaapsstraat' [id:33739362]. (Fix the minor spell differences)
    info - Found lowercase match (case problem) 'Rue de Ligne - de Lignestraat' [id:3506767975] vs. 'Rue de Ligne - De Lignestraat' [id:15064614]. (Fix the spelling)
    info - Found lowercase match (case problem) 'Rue de Ligne - de Lignestraat' [id:3506769900] vs. 'Rue de Ligne - De Lignestraat' [id:15064614]. (Fix the spelling)
    info - Found lowercase match (case problem) 'Rue de Ligne - de Lignestraat' [id:3506769908] vs. 'Rue de Ligne - De Lignestraat' [id:15064614]. (Fix the spelling)
    info - Found lowercase match (case problem) 'Rue de Ligne - de Lignestraat' [id:3506769911] vs. 'Rue de Ligne - De Lignestraat' [id:15064614]. (Fix the spelling)
    info - Found lowercase match (case problem) 'Rue de Ligne - de Lignestraat' [id:3506769922] vs. 'Rue de Ligne - De Lignestraat' [id:15064614]. (Fix the spelling)
    info - Found lowercase match (case problem) 'Rue de Ligne - de Lignestraat' [id:3506769938] vs. 'Rue de Ligne - De Lignestraat' [id:15064614]. (Fix the spelling)
    info - Found lowercase match (case problem) 'Rue de Ligne - de Lignestraat' [id:3506769944] vs. 'Rue de Ligne - De Lignestraat' [id:15064614]. (Fix the spelling)
    info - Found lowercase match (case problem) 'Rue de Ligne - de Lignestraat' [id:240849692] vs. 'Rue de Ligne - De Lignestraat' [id:15064614]. (Fix the spelling)
    info - Found deep scan match 'Treurenberg - Treurenberg' [id:240849693] vs. 'Treurenberg' [id:8511798]. (Fix the single name of the street)
    info - Found deep scan match 'Treurenberg - Treurenberg' [id:240849694] vs. 'Treurenberg' [id:8511798]. (Fix the single name of the street)
    info - Found deep scan match 'Treurenberg - Treurenberg' [id:240849698] vs. 'Treurenberg' [id:8511798]. (Fix the single name of the street)
    info - Found deep scan match 'Treurenberg - Treurenberg' [id:240849699] vs. 'Treurenberg' [id:8511798]. (Fix the single name of the street)
    info - Found deep scan match 'Treurenberg - Treurenberg' [id:240849720] vs. 'Treurenberg' [id:8511798]. (Fix the single name of the street)
    info - Found deep scan match 'Arenbergstraat - Rue d'Arenberg' [id:306856272] vs. 'Rue d'Arenberg - Arenbergstraat' [id:8511786]. (Fix the order)
    info - Found deep scan match 'Arenbergstraat - Rue d'Arenberg' [id:306856288] vs. 'Rue d'Arenberg - Arenbergstraat' [id:8511786]. (Fix the order)
    info - Found deep scan match 'Arenbergstraat - Rue d'Arenberg' [id:306856302] vs. 'Rue d'Arenberg - Arenbergstraat' [id:8511786]. (Fix the order)
    info - Found lowercase match (case problem) 'Rue T'Serclaes - T'Serclaesstraat' [id:343874437] vs. 'Rue t'Serclaes - t'Serclaesstraat' [id:23093742]. (Fix the spelling)
    info - Found lowercase match (case problem) 'Rue T'Serclaes - T'Serclaesstraat' [id:343874440] vs. 'Rue t'Serclaes - t'Serclaesstraat' [id:23093742]. (Fix the spelling)

Now you can use JOSM to search for those ID's.  Nice example is [3261888514](http://www.openstreetmap.org/node/3261888514) , it's somewhere far away from the real location of this street.  I will not fix it. That is a hard error.  The rest has the suggested fix printed.

## suggestions

I parsed a file until I catched all common errors, there might be a lot more.  The spelling is also checked with levenshtein distance check.  This catches a nasty problem I discoverd with utf8 encodings, so a few spelling mistakes

##
