drop database sample;
create database sample;
use phpmyadmin;
use sample;

-- 1. UTENTE
CREATE TABLE UTENTE (
    indirizzoEmail VARCHAR(255) PRIMARY KEY,
    nickname       VARCHAR(255) NOT NULL UNIQUE,
    password_      VARCHAR(255) NOT NULL,
    nome           VARCHAR(255) NOT NULL,
    cognome        VARCHAR(255) NOT NULL,
    anno_nascita   INT NOT NULL,
    luogo_nascita  VARCHAR(255) NOT NULL
);

-- 2. AMMINISTRATORE
CREATE TABLE AMMINISTRATORE (
    indirizzoEmailUtente VARCHAR(255) PRIMARY KEY,
    codice_sicurezza     VARCHAR(255) NOT NULL,
    FOREIGN KEY (indirizzoEmailUtente) REFERENCES UTENTE(indirizzoEmail) 
	ON DELETE CASCADE
);

-- 3. CREATORE
CREATE TABLE CREATORE (
    indirizzoEmailUtente VARCHAR(255) PRIMARY KEY,
    affidabilita         DECIMAL(5,2), #percentuale
    nr_progetti          INT default 0,
    FOREIGN KEY (indirizzoEmailUtente) REFERENCES UTENTE(indirizzoEmail)
	ON DELETE CASCADE,
    CHECK (affidabilita BETWEEN 0 AND 100),
    CHECK (nr_progetti >= 0)
);

-- 4. COMPETENZA
CREATE TABLE COMPETENZA (
    nome VARCHAR(255) PRIMARY KEY,
    indirizzoEmailAmministratore VARCHAR(255) NOT NULL,
    FOREIGN KEY (indirizzoEmailAmministratore) REFERENCES AMMINISTRATORE(indirizzoEmailUtente)
	ON DELETE CASCADE
);

-- 5. PROGETTO
CREATE TABLE PROGETTO (
    nome VARCHAR(255) PRIMARY KEY,
    descrizione TEXT,
    data_inserimento DATE NOT NULL,
    data_limite DATE NOT NULL,
    budget DECIMAL(10,2) NOT NULL comment 'valuta: EUR',
    stato ENUM('aperto','chiuso') NOT NULL,
    indirizzoEmailCreatore VARCHAR(255) NOT NULL,
    FOREIGN KEY (indirizzoEmailCreatore) REFERENCES CREATORE(indirizzoEmailUtente)
	ON DELETE CASCADE
);

-- 6. FOTO
CREATE TABLE FOTO (
    nomeProgetto VARCHAR(255) PRIMARY KEY,
    immagine     VARCHAR(255) NOT NULL,
    FOREIGN KEY (nomeProgetto) REFERENCES PROGETTO(nome)
	ON DELETE CASCADE
);

-- 7. COMPONENTE
CREATE TABLE COMPONENTE (
    nome         VARCHAR(255) PRIMARY KEY,
    descrizione  TEXT NOT NULL, 
    prezzo       DECIMAL(10,2) NOT NULL,
    quantita     INT NOT NULL, 
    nomeProgetto VARCHAR(255) NOT NULL,
    FOREIGN KEY (nomeProgetto) REFERENCES PROGETTO(nome)
	ON DELETE CASCADE,
    CHECK (quantita > 0)
);

-- 8. REWARD
CREATE TABLE REWARD (
    codice       VARCHAR(255) PRIMARY KEY,
    descrizione  TEXT,
    foto         VARCHAR(255) NOT NULL,
    nomeProgetto VARCHAR(255) NOT NULL,
    FOREIGN KEY (nomeProgetto) REFERENCES PROGETTO(nome)
	ON DELETE CASCADE
);

-- 9. PROFILO
CREATE TABLE PROFILO (
    id           INT PRIMARY KEY auto_increment,
    nome         VARCHAR(255) NOT NULL,
    nomeProgetto VARCHAR(255) NOT NULL,
    FOREIGN KEY (nomeProgetto) REFERENCES PROGETTO(nome)
      ON DELETE CASCADE
);

-- 10. INDICARE(skill di curriculum)
CREATE TABLE INDICARE (
    indirizzoEmailUtente VARCHAR(255),
    nomeCompetenza       VARCHAR(255),
    livello              INT NOT NULL,
    PRIMARY KEY (indirizzoEmailUtente, nomeCompetenza),
    FOREIGN KEY (indirizzoEmailUtente) REFERENCES UTENTE(indirizzoEmail)
	ON DELETE CASCADE,
    FOREIGN KEY (nomeCompetenza) REFERENCES COMPETENZA(nome)
	ON DELETE CASCADE,
    CHECK (livello BETWEEN 0 AND 5)
);

-- 11. RICHIEDERE(skill richiesti dal profilo)
CREATE TABLE RICHIEDERE (
    idProfilo      INT,
    nomeCompetenza VARCHAR(255),
    livello        INT NOT NULL,
    PRIMARY KEY (idProfilo, nomeCompetenza),
    FOREIGN KEY (idProfilo) REFERENCES PROFILO(id)
	ON DELETE CASCADE,
    FOREIGN KEY (nomeCompetenza) REFERENCES COMPETENZA(nome)
	ON DELETE CASCADE,
    CHECK (livello BETWEEN 0 AND 5)
);

-- 12. FINANZIAMENTO
CREATE TABLE FINANZIAMENTO (
    indirizzoEmailUtente VARCHAR(255),
    nomeProgetto         VARCHAR(255),
    data_                DATE NOT NULL,
    importo              DECIMAL(10,2) NOT NULL comment 'valuta: EUR',
    codiceReward         VARCHAR(255) NOT NULL,
    PRIMARY KEY (indirizzoEmailUtente, nomeProgetto, data_),
    FOREIGN KEY (indirizzoEmailUtente) REFERENCES UTENTE(indirizzoEmail)
	ON DELETE CASCADE,
    FOREIGN KEY (nomeProgetto) REFERENCES PROGETTO(nome)
	ON DELETE CASCADE,
    FOREIGN KEY (codiceReward) REFERENCES REWARD(codice)
	ON DELETE CASCADE
);

-- 13. COMMENTO
CREATE TABLE COMMENTO (
    id                   INT PRIMARY KEY AUTO_INCREMENT,
    data_                DATE NOT NULL,
    testo                TEXT NOT NULL,
    indirizzoEmailUtente VARCHAR(255) NOT NULL,
    nomeProgetto         VARCHAR(255) NOT NULL,
    FOREIGN KEY (indirizzoEmailUtente) REFERENCES UTENTE(indirizzoEmail)
	ON DELETE CASCADE,
    FOREIGN KEY (nomeProgetto) REFERENCES PROGETTO(nome)
	ON DELETE CASCADE
);

-- 14. RISPOSTA
CREATE TABLE RISPOSTA (
    idCommento             INT PRIMARY KEY,
    data_                  DATE NOT NULL,
    testo                  TEXT NOT NULL,
    indirizzoEmailCreatore VARCHAR(255) NOT NULL,
    FOREIGN KEY (indirizzoEmailCreatore) REFERENCES CREATORE(indirizzoEmailUtente)
	ON DELETE CASCADE,
    FOREIGN KEY (idCommento) REFERENCES COMMENTO(id)
	ON DELETE CASCADE
);

-- 15. CANDIDATURA
CREATE TABLE CANDIDATURA (
    indirizzoEmailUtente VARCHAR(255),
    idProfilo            INT,
    PRIMARY KEY (indirizzoEmailUtente, idProfilo),
    FOREIGN KEY (indirizzoEmailUtente) REFERENCES UTENTE(indirizzoEmail)
	ON DELETE CASCADE,
    FOREIGN KEY (idProfilo) REFERENCES PROFILO(id)
	ON DELETE CASCADE
);

-- 16. ACCETTARE(lista delle candidature accettate)
CREATE TABLE ACCETTARE (
    indirizzoEmailUtente   VARCHAR(255),
    idProfilo              INT,
    indirizzoEmailCreatore VARCHAR(255),
    PRIMARY KEY (indirizzoEmailUtente, idProfilo, indirizzoEmailCreatore),
    FOREIGN KEY (indirizzoEmailUtente) REFERENCES UTENTE(indirizzoEmail)
	ON DELETE CASCADE,
    FOREIGN KEY (idProfilo) REFERENCES PROFILO(id)
	ON DELETE CASCADE,
    FOREIGN KEY (indirizzoEmailCreatore) REFERENCES CREATORE(indirizzoEmailUtente)
	ON DELETE CASCADE
);

-- 17. RIFIUTARE(lista delle candidature rifiutate)
CREATE TABLE RIFIUTARE (
    indirizzoEmailUtente   VARCHAR(255),
    idProfilo              INT,
    indirizzoEmailCreatore VARCHAR(255),
    PRIMARY KEY (indirizzoEmailUtente, idProfilo, indirizzoEmailCreatore),
    FOREIGN KEY (indirizzoEmailUtente) REFERENCES UTENTE(indirizzoEmail)
	ON DELETE CASCADE,
    FOREIGN KEY (idProfilo) REFERENCES PROFILO(id)
	ON DELETE CASCADE,
    FOREIGN KEY (indirizzoEmailCreatore) REFERENCES CREATORE(indirizzoEmailUtente)
	ON DELETE CASCADE
);












































