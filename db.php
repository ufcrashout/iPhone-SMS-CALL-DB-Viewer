<?php
// db.php

function getMessageDbConnection() {
    // Connection to the messages SQLite database
    $db = new PDO('sqlite:/path/to/sms.db');  // Update path to messages db as needed
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $db;
}

function getCallDbConnection() {
    // Connection to the calls SQLite database
    $db = new PDO('sqlite:/path/to/CallHistory.storedata');  // Update path to calls db as needed
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $db;
}

// New function to connect to AddressBook database
function getAddressBookDbConnection() {
    $db = new PDO('sqlite:/path/to/AddressBook.sqlitedb');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $db;
}

// Retrieve contact name by phone number
function getContactName($phone_number) {
    $addressBookDb = getAddressBookDbConnection();
    
    // Search for the phone number in ABMultiValue and get the associated record_id
    $stmt = $addressBookDb->prepare("
        SELECT ABPerson.first AS first_name, ABPerson.last AS last_name
        FROM ABMultiValue
        JOIN ABPerson ON ABMultiValue.record_id = ABPerson.ROWID
        WHERE ABMultiValue.value = :phone_number AND ABMultiValue.property = 3
    ");
    $stmt->execute([':phone_number' => $phone_number]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        return trim($result['first_name'] . ' ' . $result['last_name']);
    }
    return null; // Return null if no contact name is found
}
?>
