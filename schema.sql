CREATE DATABASE ipmhDB;
USE ipmhDB;
GO

CREATE TABLE WARD (
    WardName    VARCHAR(100)    NOT NULL,
    Specialty   VARCHAR(100),
    CONSTRAINT PK_WARD PRIMARY KEY (WardName)
);

CREATE TABLE NURSE (
    StaffNo     INT             NOT NULL,
    Name        VARCHAR(100)    NOT NULL,
    Type        VARCHAR(20)     NOT NULL CHECK (Type IN ('DaySister', 'NightSister', 'NonReg')),
    CareUnitNo  INT             NULL,
    CONSTRAINT PK_NURSE PRIMARY KEY (StaffNo)
);

CREATE TABLE CARE_UNIT (
    CareUnitNo      INT             NOT NULL,
    WardName        VARCHAR(100)    NOT NULL,
    InChargeNurseNo INT             NOT NULL,
    CONSTRAINT PK_CARE_UNIT  PRIMARY KEY (CareUnitNo),
    CONSTRAINT FK_CU_WARD    FOREIGN KEY (WardName)
        REFERENCES WARD(WardName)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT FK_CU_NURSE   FOREIGN KEY (InChargeNurseNo)
        REFERENCES NURSE(StaffNo)
);

ALTER TABLE NURSE
    ADD CONSTRAINT FK_NURSE_CU FOREIGN KEY (CareUnitNo)
        REFERENCES CARE_UNIT(CareUnitNo)
        ON DELETE SET NULL
        ON UPDATE CASCADE;

CREATE TABLE PATIENT (
    PatientNo       INT             NOT NULL,
    PatientName     VARCHAR(100)    NOT NULL,
    DateOfBirth     DATE,
    BedNo           INT,
    DateAdmitted    DATE,
    CareUnitNo      INT             NOT NULL,
    CONSTRAINT PK_PATIENT  PRIMARY KEY (PatientNo),
    CONSTRAINT FK_PAT_CU   FOREIGN KEY (CareUnitNo)
        REFERENCES CARE_UNIT(CareUnitNo)
);

CREATE TABLE COMPLAINT (
    ComplaintCode   INT             NOT NULL,
    Description     VARCHAR(255),
    CONSTRAINT PK_COMPLAINT PRIMARY KEY (ComplaintCode)
);

CREATE TABLE TREATMENT (
    TreatmentCode   INT             NOT NULL,
    Description     VARCHAR(255),
    CONSTRAINT PK_TREATMENT PRIMARY KEY (TreatmentCode)
);

CREATE TABLE RECEIVES_TREATMENT (
    PatientNo       INT             NOT NULL,
    ComplaintCode   INT             NOT NULL,
    TreatmentCode   INT             NOT NULL,
    DateStarted     DATE,
    DateEnded       DATE,
    CONSTRAINT PK_RT PRIMARY KEY (PatientNo, ComplaintCode, TreatmentCode),
    CONSTRAINT FK_RT_PAT       FOREIGN KEY (PatientNo)
        REFERENCES PATIENT(PatientNo)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT FK_RT_COMPLAINT FOREIGN KEY (ComplaintCode)
        REFERENCES COMPLAINT(ComplaintCode)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT FK_RT_TREATMENT FOREIGN KEY (TreatmentCode)
        REFERENCES TREATMENT(TreatmentCode)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE DOCTOR (
    DoctorNo            INT             NOT NULL,
    Name                VARCHAR(100)    NOT NULL,
    Position            VARCHAR(100),
    DateJoinedTeam      DATE,
    InChargeCareUnitNo  INT,
    ConsultantNo        INT,
    CONSTRAINT PK_DOCTOR  PRIMARY KEY (DoctorNo),
    CONSTRAINT FK_DOC_CU  FOREIGN KEY (InChargeCareUnitNo)
        REFERENCES CARE_UNIT(CareUnitNo)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

CREATE TABLE CONSULTANT (
    DoctorNo    INT             NOT NULL,
    Specialty   VARCHAR(100),
    CONSTRAINT PK_CONSULTANT  PRIMARY KEY (DoctorNo),
    CONSTRAINT FK_CONS_DOC    FOREIGN KEY (DoctorNo)
        REFERENCES DOCTOR(DoctorNo)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE RECORD (
    DoctorNo    INT     NOT NULL,
    CONSTRAINT PK_RECORD   PRIMARY KEY (DoctorNo),
    CONSTRAINT FK_REC_DOC  FOREIGN KEY (DoctorNo)
        REFERENCES DOCTOR(DoctorNo)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE PREV_EXPERIENCE (
    DoctorNo        INT             NOT NULL,
    FromDate        DATE            NOT NULL,
    Position        VARCHAR(100),
    Establishment   VARCHAR(150),
    ToDate          DATE,
    CONSTRAINT PK_PREV_EXP  PRIMARY KEY (DoctorNo, FromDate),
    CONSTRAINT FK_PE_RECORD  FOREIGN KEY (DoctorNo)
        REFERENCES RECORD(DoctorNo)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE PERFORMANCE (
    DoctorNo    INT     NOT NULL,
    ReviewDate  DATE    NOT NULL,
    Grade       VARCHAR(10),
    CONSTRAINT PK_PERFORMANCE  PRIMARY KEY (DoctorNo, ReviewDate),
    CONSTRAINT FK_PERF_RECORD  FOREIGN KEY (DoctorNo)
        REFERENCES RECORD(DoctorNo)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- WARD
INSERT INTO WARD (WardName, Specialty) VALUES
('Ward_A', 'Cardiology'),
('Ward_B', 'Orthopedics'),
('Ward_C', 'Neurology'),
('Ward_D', 'Oncology'),
('Ward_E', 'Pediatrics'),
('Ward_F', 'General Surgery'),
('Ward_G', 'Psychiatry'),
('Ward_H', 'Dermatology');

-- NURSE (no WardName, 3NF compliant)
INSERT INTO NURSE (StaffNo, Name, Type, CareUnitNo) VALUES
(101, 'Sara Ahmed',       'DaySister',   NULL),
(102, 'Nadia Khan',       'NightSister', NULL),
(103, 'Usman Ali',        'NonReg',      NULL),
(104, 'Fatima Malik',     'DaySister',   NULL),
(105, 'Hira Yousaf',      'NightSister', NULL),
(106, 'Zainab Siddiqui',  'NonReg',      NULL),
(107, 'Bilal Rehman',     'DaySister',   NULL),
(108, 'Amna Tariq',       'NightSister', NULL),
(109, 'Kamran Javed',     'NonReg',      NULL),
(110, 'Sana Iqbal',       'DaySister',   NULL),
(111, 'Rabia Chaudhry',   'NightSister', NULL),
(112, 'Asad Mehmood',     'NonReg',      NULL),
(113, 'Mehwish Baig',     'DaySister',   NULL),
(114, 'Tariq Hussain',    'NightSister', NULL),
(115, 'Ayesha Farooq',    'NonReg',      NULL);

-- CARE_UNIT (InChargeNurseNo references nurses inserted above)
INSERT INTO CARE_UNIT (CareUnitNo, WardName, InChargeNurseNo) VALUES
(1,  'Ward_A', 101),
(2,  'Ward_A', 102),
(3,  'Ward_B', 103),
(4,  'Ward_B', 104),
(5,  'Ward_C', 105),
(6,  'Ward_C', 106),
(7,  'Ward_D', 107),
(8,  'Ward_D', 108),
(9,  'Ward_E', 109),
(10, 'Ward_E', 110),
(11, 'Ward_F', 111),
(12, 'Ward_F', 112),
(13, 'Ward_G', 113),
(14, 'Ward_H', 114),
(15, 'Ward_H', 115);

-- Update nurses with their CareUnitNo
UPDATE NURSE SET CareUnitNo = 1  WHERE StaffNo = 101;
UPDATE NURSE SET CareUnitNo = 2  WHERE StaffNo = 102;
UPDATE NURSE SET CareUnitNo = 3  WHERE StaffNo = 103;
UPDATE NURSE SET CareUnitNo = 4  WHERE StaffNo = 104;
UPDATE NURSE SET CareUnitNo = 5  WHERE StaffNo = 105;
UPDATE NURSE SET CareUnitNo = 6  WHERE StaffNo = 106;
UPDATE NURSE SET CareUnitNo = 7  WHERE StaffNo = 107;
UPDATE NURSE SET CareUnitNo = 8  WHERE StaffNo = 108;
UPDATE NURSE SET CareUnitNo = 9  WHERE StaffNo = 109;
UPDATE NURSE SET CareUnitNo = 10 WHERE StaffNo = 110;
UPDATE NURSE SET CareUnitNo = 11 WHERE StaffNo = 111;
UPDATE NURSE SET CareUnitNo = 12 WHERE StaffNo = 112;
UPDATE NURSE SET CareUnitNo = 13 WHERE StaffNo = 113;
UPDATE NURSE SET CareUnitNo = 14 WHERE StaffNo = 114;
UPDATE NURSE SET CareUnitNo = 15 WHERE StaffNo = 115;

-- DOCTOR
INSERT INTO DOCTOR (DoctorNo, Name, Position, DateJoinedTeam, InChargeCareUnitNo) VALUES
(201, 'Dr. Kamran Raza',    'Consultant',    '2015-06-01', 1),
(202, 'Dr. Bilal Tariq',    'Junior Doctor', '2020-09-15', 2),
(203, 'Dr. Ayesha Noor',    'Consultant',    '2018-03-22', 5),
(204, 'Dr. Imran Qureshi',  'Registrar',     '2022-01-10', 3),
(205, 'Dr. Sana Mirza',     'Consultant',    '2016-07-30', 7),
(206, 'Dr. Faisal Ghani',   'Junior Doctor', '2021-04-01', 4),
(207, 'Dr. Rabia Zafar',    'Registrar',     '2019-11-12', 9),
(208, 'Dr. Hassan Sheikh',  'Consultant',    '2013-02-14', 11),
(209, 'Dr. Maryam Lodhi',   'Junior Doctor', '2023-03-05', 6),
(210, 'Dr. Usman Cheema',   'Registrar',     '2017-08-20', 13);

-- CONSULTANT (only doctors who are consultants)
INSERT INTO CONSULTANT (DoctorNo, Specialty) VALUES
(201, 'Cardiology'),
(203, 'Neurology'),
(205, 'Oncology'),
(208, 'General Surgery');

-- RECORD
INSERT INTO RECORD (DoctorNo) VALUES
(201), (202), (203), (204), (205),
(206), (207), (208), (209), (210);

-- PREV_EXPERIENCE
INSERT INTO PREV_EXPERIENCE (DoctorNo, FromDate, Position, Establishment, ToDate) VALUES
(201, '2010-01-01', 'Registrar',     'City Hospital Lahore',     '2015-05-31'),
(201, '2007-06-01', 'Intern',        'Services Hospital Lahore',  '2009-12-31'),
(202, '2018-06-01', 'Intern',        'PIMS Islamabad',            '2020-08-31'),
(203, '2013-04-01', 'Junior Doctor', 'Mayo Hospital Lahore',      '2018-02-28'),
(203, '2011-01-01', 'Intern',        'Jinnah Hospital Lahore',    '2012-12-31'),
(204, '2019-07-01', 'Intern',        'Shifa International',       '2021-12-31'),
(205, '2011-03-01', 'Registrar',     'Aga Khan Hospital Karachi', '2016-07-29'),
(205, '2008-09-01', 'Intern',        'Civil Hospital Karachi',    '2010-12-31'),
(206, '2019-01-01', 'Intern',        'Pakistan Naval Hospital',   '2021-03-31'),
(207, '2015-05-01', 'Junior Doctor', 'Benazir Bhutto Hospital',   '2019-10-30'),
(208, '2008-01-01', 'Registrar',     'Holy Family Hospital',      '2012-12-31'),
(208, '2005-06-01', 'Intern',        'DHQ Hospital Rawalpindi',   '2007-12-31'),
(209, '2021-01-01', 'Intern',        'Islamabad Medical Complex',  '2023-02-28'),
(210, '2013-09-01', 'Junior Doctor', 'CMH Rawalpindi',            '2017-07-31');

-- PERFORMANCE
INSERT INTO PERFORMANCE (DoctorNo, ReviewDate, Grade) VALUES
(201, '2021-06-30', 'A'),
(201, '2022-06-30', 'A'),
(201, '2023-06-30', 'A+'),
(202, '2022-06-30', 'B'),
(202, '2023-06-30', 'B+'),
(203, '2021-06-30', 'A'),
(203, '2022-06-30', 'A'),
(203, '2023-06-30', 'A'),
(204, '2022-06-30', 'B+'),
(204, '2023-06-30', 'A'),
(205, '2021-06-30', 'A+'),
(205, '2022-06-30', 'A'),
(205, '2023-06-30', 'A+'),
(206, '2022-06-30', 'C+'),
(206, '2023-06-30', 'B'),
(207, '2021-06-30', 'B'),
(207, '2022-06-30', 'B+'),
(207, '2023-06-30', 'A'),
(208, '2021-06-30', 'A+'),
(208, '2022-06-30', 'A+'),
(208, '2023-06-30', 'A+'),
(209, '2023-06-30', 'B'),
(210, '2021-06-30', 'B+'),
(210, '2022-06-30', 'A'),
(210, '2023-06-30', 'A');

-- COMPLAINT
INSERT INTO COMPLAINT (ComplaintCode, Description) VALUES
(501, 'Chest Pain'),
(502, 'Fractured Femur'),
(503, 'Severe Headache'),
(504, 'High Fever'),
(505, 'Abdominal Pain'),
(506, 'Shortness of Breath'),
(507, 'Back Pain'),
(508, 'Skin Rash'),
(509, 'Seizure'),
(510, 'Hypertension'),
(511, 'Diabetes Complications'),
(512, 'Fractured Wrist'),
(513, 'Appendicitis'),
(514, 'Anxiety Disorder'),
(515, 'Tumour Screening'),
(516, 'Knee Injury'),
(517, 'Migraine'),
(518, 'Urinary Tract Infection'),
(519, 'Asthma Attack'),
(520, 'Depression');

-- TREATMENT
INSERT INTO TREATMENT (TreatmentCode, Description) VALUES
(601, 'ECG and Medication'),
(602, 'Surgical Repair'),
(603, 'MRI and Pain Management'),
(604, 'IV Antibiotics'),
(605, 'Chemotherapy'),
(606, 'Physiotherapy'),
(607, 'Blood Pressure Medication'),
(608, 'Insulin Therapy'),
(609, 'Appendectomy'),
(610, 'Cognitive Behavioural Therapy'),
(611, 'Steroid Cream and Antihistamines'),
(612, 'Anti-Seizure Medication'),
(613, 'Bronchodilator Therapy'),
(614, 'Antidepressants'),
(615, 'X-Ray and Cast'),
(616, 'Biopsy'),
(617, 'Laparoscopy'),
(618, 'Oxygen Therapy'),
(619, 'Triptans and Rest'),
(620, 'Urinalysis and Antibiotics');

-- PATIENT (30 patients spread across all 15 care units)
INSERT INTO PATIENT (PatientNo, PatientName, DateOfBirth, BedNo, DateAdmitted, CareUnitNo) VALUES
(1001, 'Ali Hassan',          '1985-03-12', 1,  '2024-01-10', 1),
(1002, 'Zara Siddiqui',       '1990-07-25', 2,  '2024-02-14', 2),
(1003, 'Omar Farooq',         '1975-11-30', 3,  '2024-03-05', 5),
(1004, 'Hina Baig',           '2000-05-18', 4,  '2024-03-20', 3),
(1005, 'Saad Mehmood',        '1968-09-02', 5,  '2024-01-22', 7),
(1006, 'Maira Tariq',         '1995-12-11', 6,  '2024-04-01', 4),
(1007, 'Hamza Iqbal',         '2010-06-15', 7,  '2024-04-10', 9),
(1008, 'Sadia Rehman',        '1980-08-28', 8,  '2024-02-28', 1),
(1009, 'Faisal Nawaz',        '1972-04-04', 9,  '2024-05-01', 11),
(1010, 'Noor Fatima',         '1998-01-19', 10, '2024-05-15', 6),
(1011, 'Tahir Hussain',       '1965-07-07', 11, '2024-01-30', 7),
(1012, 'Lubna Sheikh',        '1988-10-23', 12, '2024-03-12', 2),
(1013, 'Asif Javed',          '1993-02-14', 13, '2024-06-01', 8),
(1014, 'Rania Qureshi',       '2005-11-05', 14, '2024-06-10', 10),
(1015, 'Zubair Ahmed',        '1978-03-30', 15, '2024-02-05', 5),
(1016, 'Sameena Riaz',        '1960-12-01', 16, '2024-07-01', 13),
(1017, 'Adnan Malik',         '1990-05-22', 17, '2024-07-08', 3),
(1018, 'Shaista Mirza',       '1983-08-16', 18, '2024-07-15', 14),
(1019, 'Bilal Chaudhry',      '2008-04-03', 19, '2024-07-20', 9),
(1020, 'Naila Ghani',         '1971-09-09', 20, '2024-08-01', 15),
(1021, 'Kamran Aziz',         '1955-01-25', 21, '2024-08-05', 1),
(1022, 'Farah Lodhi',         '1999-06-30', 22, '2024-08-10', 6),
(1023, 'Imran Butt',          '1987-03-18', 23, '2024-08-18', 11),
(1024, 'Ayesha Zafar',        '2012-11-12', 24, '2024-08-22', 10),
(1025, 'Nasir Cheema',        '1969-07-14', 25, '2024-09-01', 7),
(1026, 'Rabia Yousaf',        '1994-02-08', 26, '2024-09-05', 4),
(1027, 'Shoaib Anwar',        '1976-10-20', 27, '2024-09-12', 12),
(1028, 'Mehwish Awan',        '2001-05-27', 28, '2024-09-18', 5),
(1029, 'Tariq Shaheen',       '1963-12-31', 29, '2024-09-25', 8),
(1030, 'Uzma Perveen',        '1991-04-14', 30, '2024-10-01', 13);

-- RECEIVES_TREATMENT
INSERT INTO RECEIVES_TREATMENT (PatientNo, ComplaintCode, TreatmentCode, DateStarted, DateEnded) VALUES
(1001, 501, 601, '2024-01-10', '2024-01-20'),
(1001, 510, 607, '2024-01-10', '2024-01-25'),
(1002, 502, 602, '2024-02-14', '2024-03-01'),
(1002, 507, 606, '2024-02-14', '2024-03-10'),
(1003, 503, 603, '2024-03-05', NULL),
(1003, 517, 619, '2024-03-05', '2024-03-15'),
(1004, 512, 615, '2024-03-20', '2024-04-05'),
(1005, 515, 605, '2024-01-22', NULL),
(1005, 515, 616, '2024-01-22', '2024-02-01'),
(1006, 516, 602, '2024-04-01', '2024-04-20'),
(1006, 516, 606, '2024-04-01', '2024-05-01'),
(1007, 504, 604, '2024-04-10', '2024-04-18'),
(1007, 519, 613, '2024-04-10', '2024-04-15'),
(1008, 506, 618, '2024-02-28', '2024-03-07'),
(1008, 501, 601, '2024-02-28', '2024-03-10'),
(1009, 513, 609, '2024-05-01', '2024-05-04'),
(1010, 518, 620, '2024-05-15', '2024-05-22'),
(1011, 511, 608, '2024-01-30', NULL),
(1011, 510, 607, '2024-01-30', NULL),
(1012, 503, 603, '2024-03-12', '2024-03-25'),
(1013, 508, 611, '2024-06-01', '2024-06-14'),
(1014, 504, 604, '2024-06-10', '2024-06-17'),
(1014, 519, 613, '2024-06-10', '2024-06-15'),
(1015, 509, 612, '2024-02-05', NULL),
(1016, 520, 614, '2024-07-01', NULL),
(1016, 514, 610, '2024-07-01', NULL),
(1017, 507, 606, '2024-07-08', '2024-07-28'),
(1018, 508, 611, '2024-07-15', '2024-07-29'),
(1019, 504, 604, '2024-07-20', '2024-07-27'),
(1020, 518, 620, '2024-08-01', '2024-08-08'),
(1021, 501, 601, '2024-08-05', '2024-08-15'),
(1021, 510, 607, '2024-08-05', NULL),
(1022, 505, 617, '2024-08-10', '2024-08-13'),
(1023, 513, 609, '2024-08-18', '2024-08-21'),
(1024, 504, 604, '2024-08-22', '2024-08-29'),
(1025, 515, 605, '2024-09-01', NULL),
(1026, 516, 606, '2024-09-05', '2024-09-25'),
(1027, 507, 602, '2024-09-12', '2024-09-30'),
(1028, 503, 603, '2024-09-18', '2024-09-28'),
(1029, 508, 611, '2024-09-25', '2024-10-05'),
(1030, 520, 614, '2024-10-01', NULL);

UPDATE DOCTOR
SET ConsultantNo =
CASE DoctorNo
    WHEN 201 THEN NULL
    WHEN 202 THEN 201
    WHEN 203 THEN NULL
    WHEN 204 THEN 203
    WHEN 205 THEN NULL
    WHEN 206 THEN 205
    WHEN 207 THEN 205
    WHEN 208 THEN NULL
    WHEN 209 THEN 208
    WHEN 210 THEN 208
END
WHERE DoctorNo BETWEEN 201 AND 210;