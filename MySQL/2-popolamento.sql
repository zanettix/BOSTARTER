INSERT INTO UTENTE (indirizzoEmail, nickname, password_, nome, cognome, anno_nascita, luogo_nascita) VALUES
('admin@example.com',   'admin',   'adminpass',   'Alice',   'Admin',   1980, 'Rome'),
('creator@example.com', 'creator', 'creatorpass', 'Bob',     'Creator', 1985, 'Milan'),
('user1@example.com',   'user1',   'user1pass',   'Charlie', 'User',    1990, 'Florence'),
('user2@example.com',   'user2',   'user2pass',   'Diana',   'User',    1995, 'Naples');

INSERT INTO AMMINISTRATORE (indirizzoEmailUtente, codice_sicurezza) VALUES
('admin@example.com', 'admin123');

INSERT INTO CREATORE (indirizzoEmailUtente) VALUES
('creator@example.com');

INSERT INTO COMPETENZA (nome, indirizzoEmailAmministratore) VALUES
('PROGETTAZIONE', 'admin@example.com'),
('SQL', 'admin@example.com'),
('MONGODB', 'admin@example.com'),
('PHP', 'admin@example.com');

INSERT INTO PROGETTO (nome, descrizione, data_inserimento, data_limite, budget, stato, indirizzoEmailCreatore) VALUES
('DROP', 'progetto universitario1', '2025-05-01', '2025-12-12', 10000, 'aperto', 'creator@example.com');

INSERT INTO FOTO (nomeProgetto, immagine) VALUES
('DROP', 'image1.jpg');

INSERT INTO COMPONENTE (nome, descrizione, prezzo, quantita, nomeProgetto) VALUES
('Component1', 'CPU Component', 200.00, 2, 'DROP'),
('Component2', 'RAM Module', 150.00, 4, 'DROP');

INSERT INTO REWARD (codice, descrizione, foto, nomeProgetto) VALUES
('92u98dh', 'a pen', 'reward3.jpg', 'DROP');

INSERT INTO PROFILO (nome, nomeProgetto) VALUES
('manager', 'DROP');

INSERT INTO INDICARE (indirizzoEmailUtente, nomeCompetenza, livello) VALUES
('user1@example.com', 'MONGODB', 4),
('user2@example.com', 'SQL', 5);

INSERT INTO RICHIEDERE (idProfilo, nomeCompetenza, livello) VALUES
(1, 'MONGODB', 4),
(1, 'SQL', 4);

INSERT INTO FINANZIAMENTO (indirizzoEmailUtente, nomeProgetto, data_, importo, codiceReward) VALUES
('user1@example.com', 'DROP', curdate(), 500.00, '92u98dh');

INSERT INTO COMMENTO (id, data_, testo, indirizzoEmailUtente, nomeProgetto) VALUES
(3, '2023-03-01', 'Great project!', 'user1@example.com', 'DROP'),
(4, '2023-03-05', 'Looking forward to it!', 'user2@example.com', 'DROP');

INSERT INTO RISPOSTA (data_, testo, indirizzoEmailCreatore, idCommento) VALUES
('2023-03-02', 'Thank you for the feedback!', 'creator@example.com', 1);

INSERT INTO CANDIDATURA (indirizzoEmailUtente, idProfilo) VALUES
('user1@example.com', 1);

INSERT INTO ACCETTARE (indirizzoEmailUtente, idProfilo, indirizzoEmailCreatore) VALUES
('user1@example.com', 1, 'creator@example.com');

INSERT INTO RIFIUTARE (indirizzoEmailUtente, idProfilo, indirizzoEmailCreatore) VALUES
('user2@example.com', 1, 'creator@example.com');
