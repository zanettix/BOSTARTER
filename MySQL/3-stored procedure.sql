DELIMITER //
CREATE PROCEDURE signUpUtente(
    IN p_indirizzoEmail VARCHAR(255),
    IN p_nickname VARCHAR(255),
    IN p_password VARCHAR(255),
    IN p_nome VARCHAR(255),
    IN p_cognome VARCHAR(255),
    IN p_anno_nascita INT,
    IN p_luogo_nascita VARCHAR(255)
)
BEGIN
    -- Controlla se l'indirizzo email è già registrato
    IF EXISTS (
        SELECT 1 FROM UTENTE
        WHERE indirizzoEmail = p_indirizzoEmail
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Registrazione fallita: indirizzo email già esistente';
    END IF;
    
    -- Controlla se il nickname è già utilizzato
    IF EXISTS (
        SELECT 1 FROM UTENTE
        WHERE nickname = p_nickname
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Registrazione fallita: nickname già esistente';
    END IF;
    
    -- Inserisce il nuovo utente
    INSERT INTO UTENTE (indirizzoEmail, nickname, password_, nome, cognome, anno_nascita, luogo_nascita)
    VALUES (p_indirizzoEmail, p_nickname, p_password, p_nome, p_cognome, p_anno_nascita, p_luogo_nascita);
    
    SELECT 'Registrazione effettuata con successo' AS Messaggio;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE signUpCreatore(IN p_indirizzoEmail VARCHAR(255))
BEGIN
    DECLARE v_userCount INT;
    DECLARE v_creatoreCount INT;
    
    -- Controlla se l'email corrisponde a un Utente registrato
    SELECT COUNT(*) INTO v_userCount
    FROM UTENTE
	WHERE indirizzoEmail = p_indirizzoEmail;
    
    IF v_userCount = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Per essere creatori, bisogna essere prima registrati come utenti';
    END IF;
    
    -- Controlla se esiste già un creatore con lo stesso indirizzo email
    SELECT COUNT(*) INTO v_creatoreCount
    FROM CREATORE
    WHERE indirizzoEmailUtente = p_indirizzoEmail;
    
    IF v_creatoreCount > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Non ci possono essere due creatori con lo stesso indirizzo email';
    END IF;
    
    -- Inserisce il nuovo creatore
    INSERT INTO CREATORE(indirizzoEmailUtente, affidabilita, nr_progetti) VALUES (p_indirizzoEmail, 0, 0);
END //
DELIMITER ;


DELIMITER //
CREATE PROCEDURE signUpAmministratore(
    IN p_indirizzoEmail VARCHAR(255),
    IN p_codice VARCHAR(50)
)
BEGIN
    DECLARE v_userCount INT;
    DECLARE v_adminCount INT;
    -- Verifica che il codice di sicurezza sia corretto
    IF p_codice <> 'admin123' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Codice di sicurezza non valido';
    END IF;
    -- Verifica che l'email corrisponda a un utente registrato
    SELECT COUNT(*) INTO v_userCount
    FROM UTENTE
    WHERE indirizzoEmail = p_indirizzoEmail;
    IF v_userCount = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Per essere amministratori, bisogna essere prima registrati come utenti';
    END IF;
    -- Verifica che non esista già un amministratore con lo stesso indirizzo email
    SELECT COUNT(*) INTO v_adminCount
    FROM AMMINISTRATORE
    WHERE indirizzoEmailUtente = p_indirizzoEmail;
    IF v_adminCount > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Non ci possono essere due amministratori con lo stesso indirizzo email';
    END IF;
    -- Inserisce il nuovo amministratore
    INSERT INTO AMMINISTRATORE(indirizzoEmailUtente, codice_sicurezza) VALUES (p_indirizzoEmail, p_codice);
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE loginUtente(
    IN p_nickname VARCHAR(255),
    IN p_password VARCHAR(255)
)
BEGIN
    IF EXISTS (
        SELECT 1 FROM UTENTE
        WHERE nickname = p_nickname
          AND password_ = p_password
    ) THEN
        SELECT 'Login utente effettuato con successo' AS Messaggio;
    ELSE
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Credenziali utente non valide';
    END IF;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE loginCreatore(
    IN p_nickname VARCHAR(255),
    IN p_password VARCHAR(255)
)
BEGIN
    IF EXISTS (
        SELECT 1
        FROM UTENTE U
        INNER JOIN CREATORE C ON U.indirizzoEmail = C.indirizzoEmailUtente
        WHERE U.nickname = p_nickname
          AND U.password_ = p_password
    ) THEN
        SELECT 'Login creatore effettuato con successo' AS Messaggio;
    ELSE
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Credenziali creatore non valide';
    END IF;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE loginAmministratore(
    IN p_nickname VARCHAR(255),
    IN p_password VARCHAR(255),
    IN p_codice VARCHAR(50)
)
BEGIN
    -- Controlla il codice di sicurezza
    IF p_codice <> 'admin123' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Codice di sicurezza non valido';
    END IF;
    
    IF EXISTS (
        SELECT 1
        FROM UTENTE U
        INNER JOIN AMMINISTRATORE A ON U.indirizzoEmail = A.indirizzoEmailUtente
        WHERE U.nickname = p_nickname
          AND U.password_ = p_password
    ) THEN
        SELECT 'Login amministratore effettuato con successo' AS Messaggio;
    ELSE
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Credenziali amministratore non valide';
    END IF;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE indicaLivello(
    IN p_indirizzoEmail VARCHAR(255),
    IN p_nomeCompetenza VARCHAR(255),
    IN p_livello INT
)
BEGIN
    -- Controlla che il livello sia compreso tra 0 e 5
    IF p_livello < 0 OR p_livello > 5 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Livello non valido. Deve essere un numero intero compreso tra 0 e 5';
    END IF;
    
    -- Se l'utente ha già indicato un livello per quella competenza, cancella il record esistente
    DELETE FROM INDICARE 
    WHERE indirizzoEmailUtente = p_indirizzoEmail 
      AND nomeCompetenza = p_nomeCompetenza;
      
    -- Inserisce il nuovo livello nella tabella INDICARE
    INSERT INTO INDICARE(indirizzoEmailUtente, nomeCompetenza, livello)
    VALUES (p_indirizzoEmail, p_nomeCompetenza, p_livello);
    
    SELECT 'Livello assegnato correttamente' AS Messaggio;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE creaFinanziamento(IN p_indirizzoEmail VARCHAR(255),IN p_nomeProgetto VARCHAR(255),
	IN p_data DATE, IN p_importo DECIMAL(10,2), IN p_codiceReward VARCHAR(50)
)
BEGIN
    DECLARE stato_progetto VARCHAR(50);
    DECLARE cnt INT;
    -- Recupera lo stato attuale del progetto
    SELECT stato INTO stato_progetto
    FROM PROGETTO
    WHERE nome = p_nomeProgetto;
    -- Se il progetto non esiste o non è aperto, interrompi con errore
    IF stato_progetto IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Progetto non trovato';
    ELSEIF stato_progetto <> 'aperto' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Il progetto non è disponibile per il finanziamento';
    END IF;
    -- Controlla se esiste già un finanziamento per lo stesso utente, progetto e data
    SELECT COUNT(*) INTO cnt
    FROM FINANZIAMENTO
    WHERE indirizzoEmailUtente = p_indirizzoEmail
      AND nomeProgetto = p_nomeProgetto
      AND DATE(data_) = p_data;
    IF cnt > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Hai già finanziato questo progetto oggi';
    END IF;
    -- Inserisce il finanziamento solo se il controllo duplicato non rileva operazioni precedenti
    INSERT INTO FINANZIAMENTO(indirizzoEmailUtente, nomeProgetto, data_, importo, codiceReward)
    VALUES (p_indirizzoEmail, p_nomeProgetto, p_data, p_importo, p_codiceReward);
    SELECT 'Finanziamento inserito correttamente' AS Messaggio;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE inserisciCommento(
    IN p_indirizzoEmail VARCHAR(255),
    IN p_nomeProgetto VARCHAR(255),
    IN p_data DATE,
    IN p_testo TEXT
)
BEGIN
    INSERT INTO COMMENTO(data_, testo, indirizzoEmailUtente, nomeProgetto)
    VALUES(p_data, p_testo, p_indirizzoEmail, p_nomeProgetto);

    SELECT 'Commento inserito correttamente' AS Messaggio;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE inserisciCandidatura(
    IN p_indirizzoEmail VARCHAR(255),
    IN p_idProfilo INT
)
BEGIN
    DECLARE cnt INT;
    
    -- Verifica se esiste già una candidatura per quell'indirizzo email e idProfilo
    SELECT COUNT(*) INTO cnt
    FROM CANDIDATURA
    WHERE indirizzoEmailUtente = p_indirizzoEmail
      AND idProfilo = p_idProfilo;
    
    IF cnt > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Candidatura già esistente';
    END IF;
    
    -- Inserisce la nuova candidatura
    INSERT INTO CANDIDATURA(indirizzoEmailUtente, idProfilo)
    VALUES (p_indirizzoEmail, p_idProfilo);
    
    SELECT 'Candidatura inserita correttamente' AS Messaggio;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE creaCompetenza(
    IN p_nomeCompetenza VARCHAR(255),
    IN p_indirizzoEmailUtente VARCHAR(255)
)
BEGIN
    DECLARE cnt INT;

    -- Controlla se la competenza esiste già
    SELECT COUNT(*) INTO cnt
    FROM COMPETENZA
    WHERE nome = p_nomeCompetenza;

    IF cnt > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Competenza già esistente';
    ELSE
        INSERT INTO COMPETENZA(nome, indirizzoEmailAmministratore)
        VALUES(p_nomeCompetenza, p_indirizzoEmailUtente);
        SELECT 'Competenza creata correttamente' AS Messaggio;
    END IF;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE creaProgetto(
    IN p_nome VARCHAR(255),
    IN p_descrizione TEXT,
    IN p_data_inserimento DATE,
    IN p_data_limite DATE,
    IN p_budget DECIMAL(10,2),
    IN p_stato VARCHAR(50),
    IN p_indirizzoEmailCreatore VARCHAR(255)
)
BEGIN
    DECLARE cnt INT;

    -- Controlla se esiste già un progetto con lo stesso nome
    SELECT COUNT(*) INTO cnt
    FROM PROGETTO
    WHERE nome = p_nome;
    
    IF cnt > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Progetto già esistente';
    ELSE
        INSERT INTO PROGETTO(nome, descrizione, data_inserimento, data_limite, budget, stato, indirizzoEmailCreatore)
        VALUES(p_nome, p_descrizione, p_data_inserimento, p_data_limite, p_budget, p_stato, p_indirizzoEmailCreatore);
        SELECT 'Progetto creato correttamente' AS Messaggio;
    END IF;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE inserisciReward(
    IN p_codice VARCHAR(50),
    IN p_descrizione TEXT,
    IN p_foto VARCHAR(255),
    IN p_nomeProgetto VARCHAR(255)
)
BEGIN
    DECLARE cnt INT;

    -- Controlla se esiste già una reward con lo stesso codice per il progetto indicato
    SELECT COUNT(*) INTO cnt
    FROM REWARD
    WHERE codice = p_codice AND nomeProgetto = p_nomeProgetto;

    IF cnt > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Reward già esistente per questo progetto';
    ELSE
        INSERT INTO REWARD(codice, descrizione, foto, nomeProgetto)
        VALUES(p_codice, p_descrizione, p_foto, p_nomeProgetto);
        SELECT 'Reward inserita correttamente' AS Messaggio;
    END IF;
END //
DELIMITER ;


DELIMITER //
CREATE PROCEDURE inserisciRisposta(
	IN p_idCommento INT,
    IN p_data DATE,
    IN p_testo TEXT,
    IN p_indirizzoEmailCreatore VARCHAR(255)
)
BEGIN
    INSERT INTO RISPOSTA(idCommento, data_, testo, indirizzoEmailCreatore)
    VALUES(p_idCommento, p_data, p_testo, p_indirizzoEmailCreatore);
    
    SELECT 'Risposta inserita correttamente' AS Messaggio;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE inserisciProfilo(
    IN p_nomeProfilo VARCHAR(255),
    IN p_nomeProgetto VARCHAR(255)
)
BEGIN
    INSERT INTO PROFILO(nome, nomeProgetto)
    VALUES (p_nomeProfilo, p_nomeProgetto);
    
    SELECT 'Profilo inserito correttamente' AS Messaggio;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE accettaCandidatura(
    IN p_idProfilo INT,
    IN p_indirizzoEmailUtente VARCHAR(255),
    IN p_indirizzoEmailCreatore VARCHAR(255)
)
BEGIN
    -- Elimina la candidatura dalla tabella CANDIDATURA
    DELETE FROM CANDIDATURA
    WHERE idProfilo = p_idProfilo;
	-- Elimina il profilo dalla tabella CANDIDATURA
    -- Inserisce il record nella tabella ACCETTARE
    INSERT INTO ACCETTARE(indirizzoEmailUtente, idProfilo, indirizzoEmailCreatore)
    VALUES (p_indirizzoEmailUtente, p_idProfilo, p_indirizzoEmailCreatore);
    
    SELECT 'Candidatura accettata correttamente' AS Messaggio;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE rifiutaCandidatura(
    IN p_idProfilo INT,
    IN p_indirizzoEmailUtente VARCHAR(255),
    IN p_indirizzoEmailCreatore VARCHAR(255)
)
BEGIN
    -- Elimina la candidatura dalla tabella CANDIDATURA
    DELETE FROM CANDIDATURA
    WHERE idProfilo = p_idProfilo
      AND indirizzoEmailUtente = p_indirizzoEmailUtente;
      
    -- Inserisce il record nella tabella RIFIUTARE
    INSERT INTO RIFIUTARE(indirizzoEmailUtente, idProfilo, indirizzoEmailCreatore)
    VALUES (p_indirizzoEmailUtente, p_idProfilo, p_indirizzoEmailCreatore);
    
    SELECT 'Candidatura rifiutata correttamente' AS Messaggio;
END //
DELIMITER ;

DELIMITER //

CREATE PROCEDURE inserisciComponente (
    IN p_nome          VARCHAR(100),
    IN p_descrizione   TEXT,
    IN p_prezzo        DECIMAL(10,2),
    IN p_quantita      INT,
    IN p_nomeProgetto  VARCHAR(100)
)
BEGIN
    /* Validazioni */
    IF p_prezzo <= 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Il prezzo deve essere maggiore di 0';
    END IF;

    IF p_quantita <= 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'La quantità deve essere maggiore di 0';
    END IF;

    /* Inserimento */
    INSERT INTO COMPONENTE
        (nome, descrizione, prezzo, quantita, nomeProgetto)
    VALUES
        (p_nome, p_descrizione, p_prezzo, p_quantita, p_nomeProgetto);

    /* Messaggio di feedback per il chiamante */
    SELECT 'Componente inserita correttamente.' AS Messaggio;
END;
//
DELIMITER ;






