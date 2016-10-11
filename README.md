# check urbis tool

Install PHP first

## Prepare

There are 2 versions of this tool, the first attempt which loads plenty of stuff in memory and doesn't let go easily.  The other version will use an sqlite file to store nodes/ways and street information.  It's more optimised for analysing purposes.

 - [v1](https://github.com/gplv2/urbis-validate/tree/v1): Fast as long as the xml isn't too huge, really big OSM files choke my laptop, so I needed a more memory friendly version
 - [v2](https://github.com/gplv2/urbis-validate/tree/v2): Uses sqlite, you need 'pdo_sqlite' libs.  (added -s switch to skip reloading xml, for dev reasons mostly)

You can run this tool over a file you are editting in JOSM , my workflow goes like this:
 - fix the problem -> CTRL+S -> urbisvalidate -> fix problem , repeat.

We need composer to use the db abstraction library:

    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    
Then you need to install the requirements in your cloned folder

    composer install && composer dump-autoload

After that you should be ready to run the tool

# Read 

 - https://wiki.openstreetmap.org/wiki/Multilingual_names#Brussels
 - https://wiki.openstreetmap.org/wiki/Names

## Q/A checks based on adddr:street vs name

For nodes/ways containing : addr:street and
highways having a name

 - Checks for flipped naming schemes
 - small spelling mistakes
 - single streetnames in highways
 - non-matching streets vs addressed objects

Specifically made for Brussels.

## what it does

 - identifies street names of objects that do not match the name of a highway
 - identifies spelling mistakes if no match between the above
 - identifies umatched object addresses
 - identifies order of name (fr - nl)
 - verifies if name:fr + name:nl = name
 - verifies if name is missing when name:fr and name:nl are present
 
Meant to be used while working in JOSM on the osm save file (read only).  Works with relations, ways and nodes.

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
    </osm-script>

Open with JOSM and save the osm, you can validate while editing in JOSM , run the validator: 

`glenn@slicky:~/urbis/qa$ ./urbis_brussel.php -o addresses_brussel.osm`

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

## verification help

You can user [this document](http://static.lexicool.com/dictionary/XD2MX714062.pdf) to check for spelling of the street names,  be aware that the streetname depends on municipality , especially the dutch one.  For example:

Chaussée de Louvain - Leuvense Steenweg vs.  Chaussée de Louvain - Leuvensesteenweg

Both are correct depending where it is.  Doublecheck the houses, they have it correct most of the times.  Check for borders using this Overpass query:

    {{key=boundary}}
    {{value=postal_code}}

    <osm-script output="json">
        <query into="_" type="area">
            <bbox-query {{bbox}}/>
            <has-kv k="name" modv="" v="België - Belgique - Belgien"/>
        </query>
        <union into="_">
            <query type="relation">
            <has-kv k="{{key}}" v="{{value}}"/>
                <bbox-query {{bbox}}/>
            </query>
        </union>
        <print from="_" limit="" mode="meta" order="id"/>
        <print mode="meta"/>
        <recurse type="down"/>
        <print mode="meta"/>
    </osm-script>

In this example, the former is in Saint-Josse-ten-Noode / Sint-Joost-ten-Node , the latter is in 1000 Bruxelles / Brussel

Make sure you understand these subtle differences before making a change, often the streetname is the wrong one.  You can easily copy/paste the id:number format (ex. [id:8511786] ). This JOSM search syntax (press ctrl-F).  You need to verify street name spelling to know how to correct it.  Ex. Search for `name:"Arenberg"` in JOSM to find all streets with `Arenberg` in it.  For adddressed nodes search , search for `"addr:street":"Arenberg"`.  Mass edit the mistakes like this but make sure to narrow the search down.  It's gready so sometimes it will for example grab `Place, Rue, Boulevard Arenberg` alltogether.

Sometimes the fix is in the street and/or associated relations, other times it's in the addressed objects.

## Problematic street

There is an issue with 'Rue de la Grosse Tour - Wollendriestoren' vs. 'Rue de la Grosse Tour - Wollendriestorenstraat'. 

![alt text][toren1]

Both exist but in reality it's only 1 way [430855719](https://www.openstreetmap.org/way/430855719).  So we lack a street right now, building addresses can not match one if them.  JOSM validator doesn't like the second associated street I made to indicate this.  The way is now part of 2 associations: [3234845](https://www.openstreetmap.org/relation/3234845) and [6618925](https://www.openstreetmap.org/relation/6618925) , so this was to be expected.  

### 3234845
![alt text][rel3]

### 6618925
![alt text][rel6]

### Boundary
![alt text][border1]

In the direction of the way, on your left you have 1050 / Ixelles - Elsene and on your right side 1000 / Bruxelles - Brussel 


![alt text][toren2]

The common way `name:left` and `name:right` doesn't make a change but I find this awkward since we already use `name:nl` and `name:fr`.   Then there is the problem with the format of `name` itself.  Usually when a road has 2 names, both names also go into this field with a dash.  Example of this: way [Molenheidebaan - Trianondreef id:16752454](https://www.openstreetmap.org/way/16752454/)

![alt text][molen1]

If anyone has a good idea how to solve such an issue, please send feedback.

## suggestions

I parsed a file until I catched all common errors, there might be a lot more.  The spelling is also checked with levenshtein distance check.  This catches a nasty problem I discoverd with utf8 encodings, so a few spelling mistakes

##

[toren1]: https://github.com/gplv2/urbis-validate/raw/master/docs/torenstraat1.png "Rue de la Grosse Tour - Wollendriestoren"
[toren2]: https://github.com/gplv2/urbis-validate/raw/master/docs/torenstraat2.png "Rue de la Grosse Tour - Wollendriestorenstraat"
[molen1]: https://github.com/gplv2/urbis-validate/raw/master/docs/molenheide.png "Molenheidebaan - Trianondreef"
[rel3]: https://github.com/gplv2/urbis-validate/raw/master/docs/rel3234845.png "Relation 3234845"
[rel6]: https://github.com/gplv2/urbis-validate/raw/master/docs/rel6618925.png "Relation 6618925"
[border1]: https://github.com/gplv2/urbis-validate/raw/master/docs/borders.png "Border Ixelles/Bruxelles"

