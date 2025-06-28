<?php
enum ApiMessage: string {
        // Request did not match the requiredRequestStructure & method of any endpoint
    case noEndpointTriggered = "Nebylo odesláno dostatek vstupů";
        // Api endpoint returns a non ApiResponse value
        // Returned as: Message + json encoded value
    case invalidScriptReturnValue = "Server vrátil neplatnou hodnotu: ";
        // Api file upload endpoint does not receive any files
    case noFileUploaded = "Nebyl nahrán žádný soubor";
        // Api file upload endpoint receives file of and disallowed type
        // Returned as: Message + list of allowed file types + <br> + ApiMessage::fileRecieved + Filetype
    case illegalFileType = "Nepovolený typ souboru.<br>Povoleny jsou pouze soubory: ";
    case fileReceived = "Odeslaný soubor je typu: ";
        // Api file upload endpoint successfully uploads a file
    case uploadSucceeded = "Soubor byl úspěšně nahrán";
        // Api file upload endpoint fails to upload a file
        // Returned as: Message + error message
    case uploadingError = "Během nahrávání souboru došlo k chybě. Detaily: ";
        // Uploaded file size is 0
    case fileEmpty = "Nahraný soubor je prázdný";
        // Uploaded file size is greater than allowed
        // Returned as: Message + allowed file size + MB
    case fileTooBig = "Nahraný soubor je větší, než limit ";
        // User promised to send less files then required
        // Returned as: Message + number of required files
    case notEnoughFiles = "Nebyl nahrán dostatečný počet souborů. Minimální počet: ";
        // User promised to send more files then allowed
        // Returned as: Message + number of allowed files
    case tooMuchFiles = "Bylo nahráno více souborů, než je povoleno. Maximální počet: ";
        // User sent more files, than promised
    case moreFilesThanPromised = "Bylo odesláno více souborů, než bylo očekáváno.";
}
