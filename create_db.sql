CREATE DATABASE pray_tg;

CREATE TABLE users(
    id INT NOT NULL AUTO_INCREMENT,
    username VARCHAR(25) DEFAULT NULL,
    created_at DATETIME,
    is_admin BOOLEAN DEFAULT FALSE, 
    menu INT NOT NULL,
    PRIMARY KEY (id)
);

CREATE TABLE prays (
  id INT NOT NULL AUTO_INCREMENT,
  text VARCHAR(300),
  created_at DATE,
  wednesday DATE DEFAULT NULL,
  id_user INT,
  PRIMARY KEY (id),
  FOREIGN KEY (id_user) REFERENCES users(id)
);

CREATE TABLE commands (
  command VARCHAR(25) NOT NULL,
  answer VARCHAR(500),
  text_menu VARCHAR(25),
  description VARCHAR(255),
  action VARCHAR(255) DEFAULT 'send_message',
  PRIMARY KEY (command)
);

CREATE TABLE keyboards (
  id INT NOT NULL, 
  command VARCHAR(25) NOT NULL,
  position INT NOT NULL,
  style VARCHAR(10),
  PRIMARY KEY(id, position),
  FOREIGN KEY (command) REFERENCES commands(command)
)