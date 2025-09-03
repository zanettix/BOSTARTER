/*

CREATORE.AFFIDABILITA =
Se un creatore ha realizzato 12 progetti e solo 6 hanno ottenuto almeno un finanziamento, 
il rapporto è del 50% e quindi l’affidabilità (intesa come percentuale dei progetti finanziati rispetto al totale dei progetti creati) sarà pari a 50%.
Questa metrica è utile perché riflette la capacità del creatore di lanciare progetti che attirino investimenti. 
Un valore elevato indica che una quota consistente dei progetti si traduce in almeno un finanziamento, 
mentre un valore basso segnala una difficoltà nell’ottenere supporti economici per i progetti.

*/

DELIMITER //

CREATE TRIGGER trg_update_nr_progetto_e_affidabilita
AFTER INSERT ON PROGETTO
FOR EACH ROW
BEGIN
    DECLARE total INT;
    DECLARE funded INT;

    -- Incrementa nr_progetti per il creatore
    UPDATE CREATORE
    SET nr_progetti = nr_progetti + 1
    WHERE indirizzoEmailUtente = NEW.indirizzoEmailCreatore;

    -- Recupera il numero totale di progetti dal creatore (ora aggiornato)
    SELECT nr_progetti INTO total
    FROM CREATORE
    WHERE indirizzoEmailUtente = NEW.indirizzoEmailCreatore;

    -- Conta quanti progetti creati dal creatore hanno ricevuto almeno un finanziamento
    SELECT COUNT(DISTINCT f.nomeProgetto) INTO funded
    FROM FINANZIAMENTO f
    INNER JOIN PROGETTO p ON f.nomeProgetto = p.nome
    WHERE p.indirizzoEmailCreatore = NEW.indirizzoEmailCreatore;

    -- Aggiorna l'affidabilità come percentuale
    UPDATE CREATORE
    SET affidabilita = IF(total > 0, (funded / total) * 100, 0)
    WHERE indirizzoEmailUtente = NEW.indirizzoEmailCreatore;
END //
DELIMITER ;

DELIMITER //
CREATE TRIGGER trg_update_affidabilita_after_finanziamento
AFTER INSERT ON FINANZIAMENTO
FOR EACH ROW
BEGIN
    DECLARE creatorEmail VARCHAR(255);
    DECLARE total INT;
    DECLARE funded INT;

    -- Recupera l'indirizzo email del creatore in base al progetto finanziato
    SELECT indirizzoEmailCreatore INTO creatorEmail
    FROM PROGETTO
    WHERE nome = NEW.nomeProgetto;

    -- Recupera il numero totale di progetti creati dal creatore (valore già memorizzato in nr_progetti)
    SELECT nr_progetti INTO total
    FROM CREATORE
    WHERE indirizzoEmailUtente = creatorEmail;

    -- Conta quanti progetti del creatore hanno ricevuto almeno un finanziamento
    SELECT COUNT(DISTINCT f.nomeProgetto) INTO funded
    FROM FINANZIAMENTO f
    INNER JOIN PROGETTO p ON f.nomeProgetto = p.nome
    WHERE p.indirizzoEmailCreatore = creatorEmail;

    -- Aggiorna l'affidabilità del creatore (se total > 0, altrimenti 0)
    UPDATE CREATORE
    SET affidabilita = IF(total > 0, (funded / total) * 100, 0)
    WHERE indirizzoEmailUtente = creatorEmail;
END //

DELIMITER ;


DELIMITER //

CREATE TRIGGER trg_update_progetto_stato
AFTER INSERT ON FINANZIAMENTO
FOR EACH ROW
BEGIN
    DECLARE total_finanziato DECIMAL(10,2);
    DECLARE target_budget DECIMAL(10,2);
    
    -- Recupera il budget e la data limite del progetto associato al finanziamento inserito
    SELECT budget 
    INTO target_budget
    FROM PROGETTO
    WHERE nome = NEW.nomeProgetto;
    
    -- Calcola il totale dei finanziamenti effettuati per il progetto
    SELECT IFNULL(SUM(importo),0)
    INTO total_finanziato
    FROM FINANZIAMENTO
    WHERE nomeProgetto = NEW.nomeProgetto;
    
    -- Se il totale finanziato raggiunge (o supera) il budget oppure se la data limite coincide con la data odierna,
    -- aggiorna lo stato del progetto a "chiuso"
    IF total_finanziato >= target_budget THEN
        UPDATE PROGETTO
        SET stato = 'chiuso'
        WHERE nome = NEW.nomeProgetto;
    END IF;
END //

DELIMITER ;

DELIMITER //
CREATE TRIGGER verifica_eta_utente BEFORE INSERT ON UTENTE
FOR EACH ROW
BEGIN
    DECLARE eta INT;
    -- Calcola l'età dell'utente
    SET eta = YEAR(CURDATE()) - NEW.anno_nascita;
    
    -- Verifica il lower bound per l'anno di nascita, ad esempio 1900
    IF NEW.anno_nascita < 1900 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Anno di nascita troppo basso. Registrazione non consentita.';
    END IF;
    
    -- Verifica che l'utente abbia almeno 18 anni
    IF eta < 18 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'L''utente deve avere almeno 18 anni.';
    END IF;
END//
DELIMITER ;




CREATE VIEW classifica_creatori_affidabili AS
SELECT u.nickname, affidabilita
FROM CREATORE
INNER JOIN UTENTE AS u ON u.indirizzoEmail = indirizzoEmailUtente
ORDER BY affidabilita DESC, nr_progetti DESC;

CREATE VIEW view_progetti_vicinanza AS
SELECT 
    p.nome,
    p.descrizione,
    p.data_inserimento,
    p.data_limite,
    p.budget,
    p.stato,
    IFNULL(SUM(f.importo), 0) AS total_funding,
    (p.budget - IFNULL(SUM(f.importo), 0)) AS gap
FROM PROGETTO p
LEFT JOIN FINANZIAMENTO f ON p.nome = f.nomeProgetto
WHERE p.stato = 'aperto'
GROUP BY p.nome, p.descrizione, p.data_inserimento, p.data_limite, p.budget, p.stato
ORDER BY gap ASC;

CREATE VIEW view_classifica_finanziatori AS
SELECT 
    u.nickname,
    f.indirizzoEmailUtente,
    SUM(f.importo) AS totale_finanziamenti
FROM FINANZIAMENTO f
INNER JOIN UTENTE u ON f.indirizzoEmailUtente = u.indirizzoEmail
GROUP BY f.indirizzoEmailUtente, u.nickname
ORDER BY totale_finanziamenti DESC;







CREATE EVENT `ev_chiusura_progetti` 
ON SCHEDULE EVERY 1 DAY STARTS '2025-04-11 15:00:00.000000' 
ON COMPLETION NOT PRESERVE DISABLE 
COMMENT '\"Chiude i progetti se la data attuale supera la data limite\"' 
DO UPDATE PROGETTO SET stato = 'chiuso' WHERE CURDATE() > data_limite AND stato = 'aperto';















