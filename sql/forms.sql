-- CREATE TABLE form (
-- 	id INTEGER NOT NULL AUTO_INCREMENT,
-- 	slug VARCHAR(32) NOT NULL,
-- 	title VARCHAR(255) NOT NULL,
-- 	sort_order INTEGER NOT NULL,
-- 	description TEXT NOT NULL,
-- 	is_checklist BOOLEAN NOT NULL,
-- 	is_default BOOLEAN NOT NULL,
-- 	has_notes BOOLEAN NOT NULL,
-- 	intro_html TEXT,
-- 	notes TEXT,
-- 	created TIMESTAMP,
-- 	PRIMARY KEY (id)
-- );

INSERT INTO form VALUES (101, 'hoist', 'Hoisting', 1010, '', 0, 1, 1, '', '', CURRENT_TIMESTAMP());
INSERT INTO form VALUES (102, 'bowel', 'Bowel Management', 1020, '', 0, 1, 1, '', '', CURRENT_TIMESTAMP());
INSERT INTO form VALUES (103, 'records', 'Daily Records', 1030, '', 0, 1, 1, '', '', CURRENT_TIMESTAMP());
INSERT INTO form VALUES (104, 'fluid', 'Fluid Intake', 1040, '', 0, 1, 1, '', '', CURRENT_TIMESTAMP());
INSERT INTO form VALUES (105, 'meds', 'Medication Chart', 1050, '', 0, 1, 1, '', '', CURRENT_TIMESTAMP());
INSERT INTO form VALUES (106, 'neb', 'Nebuliser', 1060, '', 0, 1, 1, '', '', CURRENT_TIMESTAMP());
INSERT INTO form VALUES (107, 'commode', 'Commode', 1070, '', 0, 1, 1, '', '', CURRENT_TIMESTAMP());
INSERT INTO form VALUES (108, 'trachi', 'Trachi Cleaning and Dressing', 1080, '', 0, 1, 1, '', '', CURRENT_TIMESTAMP());
INSERT INTO form VALUES (109, 'yankeur', 'Yankeur Suction', 1090, '', 0, 1, 1, '', '', CURRENT_TIMESTAMP());
INSERT INTO form VALUES (110, 'catheter', 'Catheter Suction', 1100, '', 0, 1, 1, '', '', CURRENT_TIMESTAMP());
INSERT INTO form VALUES (111, 'cough', 'Cough Assist', 1110, '', 0, 1, 1, '', '', CURRENT_TIMESTAMP());
INSERT INTO form VALUES (112, 'daily', 'Daily Checklist', 1120, '', 1, 1, 1, '', '', CURRENT_TIMESTAMP());
INSERT INTO form VALUES (113, 'stock', 'Stock Management', 1130, '', 0, 1, 1, '', '', CURRENT_TIMESTAMP());
INSERT INTO form VALUES (114, 'night', 'Night Checklist', 1140, '', 1, 1, 1, '', '', CURRENT_TIMESTAMP());
INSERT INTO form VALUES (115, 'day', 'Day Checklist', 1150, '', 1, 1, 1, '', '', CURRENT_TIMESTAMP());

UPDATE form SET intro_html='
<div class="row">
<div class="col-md-6"><iframe width=100% height=100% src="https://www.youtube.com/embed/_bgCOOMYBDg" frameborder="0" allowfullscreen></iframe></div>
<div class="col-md-6"><iframe width=100% height=100% src="https://www.youtube.com/embed/C5XVI9_zwQo" frameborder="0" allowfullscreen></iframe></div>
</div>
<div class="row">
<div class="col-md-12">
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce pulvinar tellus et lacus euismod imperdiet nec et ex. Nunc efficitur nulla odio, in tempor tortor congue in. In hac habitasse platea dictumst. Proin blandit lacus eget ligula luctus venenatis. Phasellus feugiat mollis lorem vitae iaculis. Cras nisl lectus, iaculis id tristique a, dapibus vitae augue. In eget tortor tortor. Aliquam semper lacus vel eros placerat efficitur. Nunc ut nibh magna.</p>
<p>Vestibulum in ullamcorper lorem, et finibus sapien. Praesent a porta lorem. In rhoncus rhoncus nibh quis fringilla. Duis vestibulum dolor massa, sit amet sodales metus commodo facilisis. Sed quis velit fringilla leo vehicula fermentum. Maecenas fermentum libero fermentum, auctor urna vitae, iaculis ex. Integer consequat ligula in elit porttitor euismod. Sed sollicitudin ex id finibus fringilla. Praesent sagittis lectus nulla, pulvinar facilisis lacus varius et. Praesent vitae bibendum nunc. Proin a fermentum lorem. Etiam aliquet, ex in semper auctor, elit dui varius dolor, in tincidunt nisl ipsum in mauris.</p>
</div>
</div>
' WHERE id=101;

-- CREATE TABLE field (
-- 	id INTEGER NOT NULL AUTO_INCREMENT,
-- 	form_id INTEGER NOT NULL REFERENCES form(id),
-- 	slug VARCHAR(32) NOT NULL,
-- 	type VARCHAR(32) NOT NULL,
-- 	sort_order INTEGER NOT NULL,
-- 	label VARCHAR(255) NOT NULL,
-- 	help_text TEXT NOT NULL,
-- 	default_value VARCHAR(255),
-- 	enumeration TEXT,
-- 	regex TEXT,
-- 	minimum INTEGER,
-- 	maximum INTEGER,
-- 	format VARCHAR(255),
-- 	PRIMARY KEY (id)
-- );

CREATE TABLE submission_form_101 (
	submission_id INTEGER NOT NULL REFERENCES submission(id),
	time TEXT,
	concerns TEXT,
	PRIMARY KEY (submission_id)
);
INSERT INTO field VALUES (1011, 101, 'time', 'time', 1, 'Time', '', NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO field VALUES (1012, 101, 'concerns', 'boolean', 2, 'Concerns', 'If yes, please enter your concern as a note.', NULL, NULL, NULL, NULL, NULL, NULL);

CREATE TABLE submission_form_102 (
	submission_id INTEGER NOT NULL REFERENCES submission(id),
	time TEXT,
	colour TEXT,
	amount TEXT,
	charttype TEXT,
	PRIMARY KEY (submission_id)
);
INSERT INTO field VALUES (1021, 102, 'time', 'time', 1, 'Time', '', NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO field VALUES (1022, 102, 'colour', 'text', 2, 'Colour', '', NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO field VALUES (1023, 102, 'amount', 'select', 3, 'Result Amount', '', NULL, 'S|M|L|XL', NULL, NULL, NULL, NULL);
INSERT INTO field VALUES (1024, 102, 'charttype', 'number', 4, 'Chart Type', '', NULL, NULL, '/^[1-8]$/', 1, 8, NULL);

CREATE TABLE submission_form_103 (
	submission_id INTEGER NOT NULL REFERENCES submission(id),
	time TEXT,
	PRIMARY KEY (submission_id)
);
INSERT INTO field VALUES (1031, 103, 'time', 'time', 1, 'Time', '', NULL, NULL, NULL, NULL, NULL, NULL);

CREATE TABLE submission_form_104 (
	submission_id INTEGER NOT NULL REFERENCES submission(id),
	time TEXT,
	amount TEXT,
	type TEXT,
	concerns TEXT,
	PRIMARY KEY (submission_id)
);
INSERT INTO field VALUES (1041, 104, 'time', 'time', 1, 'Time', '', NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO field VALUES (1042, 104, 'amount', 'text', 2, 'Amount', '', NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO field VALUES (1043, 104, 'type', 'text', 3, 'Type', 'e.g. tea', NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO field VALUES (1044, 104, 'concerns', 'boolean', 4, 'Concerns', 'If yes, please enter your concern as a note.', NULL, NULL, NULL, NULL, NULL, NULL);

CREATE TABLE submission_form_107 (
	submission_id INTEGER NOT NULL REFERENCES submission(id),
	time TEXT,
	concerns TEXT,
	PRIMARY KEY (submission_id)
);
INSERT INTO field VALUES (1071, 107, 'time', 'time', 1, 'Time', '', NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO field VALUES (1072, 107, 'concerns', 'boolean', 2, 'Concerns', 'If yes, please enter your concern as a note.', NULL, NULL, NULL, NULL, NULL, NULL);

CREATE TABLE submission_form_108 (
	submission_id INTEGER NOT NULL REFERENCES submission(id),
	time TEXT,
	concerns TEXT,
	PRIMARY KEY (submission_id)
);
INSERT INTO field VALUES (1081, 108, 'time', 'time', 1, 'Time', '', NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO field VALUES (1082, 108, 'concerns', 'boolean', 2, 'Concerns', 'If yes, please enter your concern as a note.', NULL, NULL, NULL, NULL, NULL, NULL);

CREATE TABLE submission_form_109 (
	submission_id INTEGER NOT NULL REFERENCES submission(id),
	time TEXT,
	secretiondesc TEXT,
	secretionamt TEXT,
	position TEXT,
	PRIMARY KEY (submission_id)
);
INSERT INTO field VALUES (1091, 109, 'time', 'time', 1, 'Time', '', NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO field VALUES (1092, 109, 'secretiondesc', 'text', 2, 'Secretion Description', '', NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO field VALUES (1093, 109, 'secretionamt', 'select', 3, 'Secretion Amount', '', NULL, 'X|XX|XXX', NULL, NULL, NULL, NULL);
INSERT INTO field VALUES (1094, 109, 'position', 'select', 4, 'Client Position', '', NULL, 'Bed|Chair|Other', NULL, NULL, NULL, NULL);

CREATE TABLE submission_form_110 (
	submission_id INTEGER NOT NULL REFERENCES submission(id),
	time TEXT,
	secretiondesc TEXT,
	secretionamt TEXT,
	position TEXT,
	PRIMARY KEY (submission_id)
);
INSERT INTO field VALUES (1101, 110, 'time', 'time', 1, 'Time', '', NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO field VALUES (1102, 110, 'secretiondesc', 'text', 2, 'Secretion Description', '', NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO field VALUES (1103, 110, 'secretionamt', 'select', 3, 'Secretion Amount', '', NULL, 'X|XX|XXX', NULL, NULL, NULL, NULL);
INSERT INTO field VALUES (1104, 110, 'position', 'select', 4, 'Client Position', '', NULL, 'Bed|Chair|Other', NULL, NULL, NULL, NULL);

CREATE TABLE submission_form_111 (
	submission_id INTEGER NOT NULL REFERENCES submission(id),
	time TEXT,
	secretiondesc TEXT,
	secretionamt TEXT,
	position TEXT,
	PRIMARY KEY (submission_id)
);
INSERT INTO field VALUES (1111, 111, 'time', 'time', 1, 'Time', '', NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO field VALUES (1112, 111, 'secretiondesc', 'text', 2, 'Secretion Description', '', NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO field VALUES (1113, 111, 'secretionamt', 'select', 3, 'Secretion Amount', '', NULL, 'X|XX|XXX', NULL, NULL, NULL, NULL);
INSERT INTO field VALUES (1114, 111, 'position', 'select', 4, 'Client Position', '', NULL, 'Bed|Chair|Other', NULL, NULL, NULL, NULL);
