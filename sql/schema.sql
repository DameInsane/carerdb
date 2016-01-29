CREATE TABLE permission (
	id INTEGER NOT NULL AUTO_INCREMENT,
	name VARCHAR(255) NOT NULL,
	notes TEXT,
	PRIMARY KEY (id)
);

INSERT INTO permission VALUES (1, 'Administrator', 'Complete admin access');
INSERT INTO permission VALUES (2, 'User', 'Ability to log in to the system');
INSERT INTO permission VALUES (3, 'Time Lord', 'Ability to view historic data');

CREATE TABLE user (
	id INTEGER NOT NULL AUTO_INCREMENT,
	name VARCHAR(255) NOT NULL,
	username VARCHAR(255) NOT NULL,
	salthash VARCHAR(255) NOT NULL,
	staff_id VARCHAR(255) NOT NULL,
	phone VARCHAR(255) NOT NULL,
	notes TEXT,
	created TIMESTAMP,
	PRIMARY KEY (id)
);

INSERT INTO user VALUES (1, 'Administrator', 'root', 'funky', '1111', '0000', 'Main administrative account', CURRENT_TIMESTAMP());

CREATE TABLE user_permission (
	user_id INTEGER NOT NULL REFERENCES user(id),
	permission_id INTEGER NOT NULL REFERENCES permission(id),
	start_date TIMESTAMP NOT NULL,
	end_date TIMESTAMP NOT NULL,
	notes TEXT,
	PRIMARY KEY (user_id, permission_id)
);

INSERT INTO user_permission VALUES (1, 1, '1970-01-01 00:00:00', '2038-01-01 00:00:00', NULL);

CREATE TABLE client (
	id INTEGER NOT NULL AUTO_INCREMENT,
	name VARCHAR(255) NOT NULL,
	passphrase VARCHAR(32) NOT NULL,
	address VARCHAR(255),
	phone VARCHAR(255),
	next_of_kin VARCHAR(255),
	next_of_kin_phone VARCHAR(255),
	doctors TEXT,
	nurses TEXT,
	notes TEXT,
	created TIMESTAMP,
	PRIMARY KEY (id)
);

INSERT INTO client VALUES (1, 'Test', '', '123 Test Street', '01234 567 890', 'Example', '01234 567 899', 'Dr Dre', 'Nurse Redheart', 'Client for testing.', CURRENT_TIMESTAMP());

CREATE TABLE user_client (
	user_id INTEGER NOT NULL REFERENCES user(id),
	client_id INTEGER NOT NULL REFERENCES client(id),
	start_date TIMESTAMP NOT NULL,
	end_date TIMESTAMP NOT NULL,
	notes TEXT,
	PRIMARY KEY (user_id, client_id)
);

INSERT INTO user_client VALUES (1, 1, '1970-01-01 00:00:00', '2038-01-01 00:00:00', NULL);

CREATE TABLE form (
	id INTEGER NOT NULL AUTO_INCREMENT,
	slug VARCHAR(32) NOT NULL,
	title VARCHAR(255) NOT NULL,
	sort_order INTEGER NOT NULL,
	description TEXT NOT NULL,
	is_checklist BOOLEAN NOT NULL,
	is_default BOOLEAN NOT NULL,
	has_notes BOOLEAN NOT NULL,
	intro_html TEXT,
	notes TEXT,
	created TIMESTAMP,
	PRIMARY KEY (id)
);

INSERT INTO form VALUES (1, 'test', 'Test Form', 1000, 'This form is just a test', 0, 0, 1, '<p>Testing!</p>', 'Form for testing the system', CURRENT_TIMESTAMP());

CREATE TABLE field (
	id INTEGER NOT NULL AUTO_INCREMENT,
	form_id INTEGER NOT NULL REFERENCES form(id),
	slug VARCHAR(32) NOT NULL,
	type VARCHAR(32) NOT NULL,
	sort_order INTEGER NOT NULL,
	label VARCHAR(255) NOT NULL,
	help_text TEXT NOT NULL,
	default_value VARCHAR(255),
	enumeration TEXT,
	regex TEXT,
	minimum INTEGER,
	maximum INTEGER,
	format VARCHAR(255),
	minimum_length INTEGER,
	maximum_length INTEGER,
	PRIMARY KEY (id)
);

INSERT INTO field VALUES (1, 1, 'test1', 'text', 100, 'Some text', 'lower-case only', 'blah', NULL, '/^[a-z]+$/', NULL, NULL, NULL, NULL, NULL);
INSERT INTO field VALUES (2, 1, 'test2', 'number', 1000, 'Some number', '0 to 1000', 5, NULL, NULL, 0, 1000, '%0.2f', NULL, NULL);
INSERT INTO field VALUES (3, 1, 'test3', 'select', 10, 'Some choice', 'choose it', 'bar', 'foo|bar|baz|quux', NULL, NULL, NULL, NULL, NULL, NULL);

CREATE TABLE client_form (
	client_id INTEGER NOT NULL REFERENCES client(id),
	form_id INTEGER NOT NULL REFERENCES form(id),
	PRIMARY KEY (client_id, form_id)
);

INSERT INTO client_form VALUES (1, 1);

CREATE TABLE note (
	id INTEGER NOT NULL AUTO_INCREMENT,
	client_id INTEGER NOT NULL REFERENCES client(id),
	form_id INTEGER NOT NULL REFERENCES form(id),
	user_id INTEGER NOT NULL REFERENCES user(id),
	is_highlighted BOOLEAN NOT NULL,
	is_hidden BOOLEAN NOT NULL,
	note TEXT,
	created TIMESTAMP,
	PRIMARY KEY (id)
);

CREATE TABLE submission (
	id INTEGER NOT NULL AUTO_INCREMENT,
	client_id INTEGER NOT NULL REFERENCES client(id),
	form_id INTEGER NOT NULL REFERENCES form(id),
	user_id INTEGER NOT NULL REFERENCES user(id),
	is_highlighted BOOLEAN NOT NULL,
	is_hidden BOOLEAN NOT NULL,
	note TEXT,
	created TIMESTAMP,
	PRIMARY KEY (id)
);

CREATE TABLE submission_form_1 (
	submission_id INTEGER NOT NULL REFERENCES submission(id),
	test1 TEXT,
	test2 TEXT,
	test3 TEXT,
	PRIMARY KEY (submission_id)
);
