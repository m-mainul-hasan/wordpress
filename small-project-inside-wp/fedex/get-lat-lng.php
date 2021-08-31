<?php

/**
 * How to get lat, lng for all Fedex Ship Center.
 *
 * Get latest Fedex Ship Center excel document from Client.
 * Export the excel to CSV.
 * Import the CSV to a table using PhpMyAdmin.
 * Delete all column except required ones: LocID,LocType_Code,LocSubType_Code,LocType,LocDisplayName,LocName,Bldg,Address,Suite,LocOnProperty,Room_Floor,City,State,Zip
 * PhpMyAdmin will create column names as Col1, Col2, etc. Rename columns to valid names.
 * Add auto increment first column (uid)
 * Add two column at the end for lat, lng. Both float (10,6)
 * Delete * rows WHERE LocDisplayName != 'FedEx Ship Center'
 * Login to Mapquest to get API key.
 * Then run following code.
 */

$link = mysqli_connect("db_host", "user", "pass", "db") or die(mysqli_connect_error());
$query = "SELECT * FROM fedex_ship_centers WHERE (LocDisplayName = 'FedEx Ship Center') AND (lat IS NULL OR lng IS NULL) LIMIT 25";

$res = mysqli_query($link, $query) or die(mysqli_error($link));

if (mysqli_num_rows($res)) {
    while($row = mysqli_fetch_assoc($res)) {
        $location = "{$row['Address']}, {$row['City']}, {$row['State']} {$row['Zip']}";

        $location = rawurlencode($location);

        $mapquest_url = "http://www.mapquestapi.com/geocoding/v1/address?key=XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX&location={$location}&maxResults=1";

        $maprequest_response = json_decode(file_get_contents($mapquest_url));

        $lat = $maprequest_response->results[0]->locations[0]->latLng->lat;
        $lng = $maprequest_response->results[0]->locations[0]->latLng->lng;

        mysqli_query($link, "UPDATE fedex_ship_centers SET lat = {$lat}, lng = {$lng} WHERE id = {$row['id']}");
    }
}

