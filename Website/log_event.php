<?php
require 'vendor/autoload.php';

function logEvent($eventMessage) {
    try {
        // Crea il client MongoDB e seleziona il database e la collezione
        $mongoClient = new MongoDB\Client("mongodb://127.0.0.1:27017");
        $db = $mongoClient->bostarter_logs; // database per i log
        $collection = $db->event_log;       // collezione per i log

        // Crea l'oggetto DateTime con il fuso orario italiano
        $date = new DateTime("now", new DateTimeZone("Europe/Rome"));
        // Ottieni una rappresentazione formattata dell'orario italiano
        $localTime = $date->format("Y-m-d H:i:s");
        // Se vuoi salvare event_time come UTCDateTime ma "spostato" in base al fuso locale,
        // puoi calcolare il timestamp aggiungendo l'offset:
        $milliseconds = ($date->getTimestamp() + $date->getOffset()) * 1000;

        // Prepara il documento da inserire, salvando sia il valore "localizzato"
        // che la stringa formattata
        $document = [
            'event_time' => $localTime,
            'event_message'    => $eventMessage
        ];

        // Inserisce il documento nella collezione
        $result = $collection->insertOne($document);

        if($result->getInsertedCount() == 1) {
            return true;
        } else {
            return false;
        }
    } catch (Exception $e) {
        error_log("Errore in logEvent: " . $e->getMessage());
        return false;
    }
}
?>

