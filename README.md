<div align="center">

# Ivor Paine Memorial Hospital
### Hospital Management System

![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![SQL Server](https://img.shields.io/badge/SQL_Server-Microsoft-CC2927?style=for-the-badge&logo=microsoftsqlserver&logoColor=white)
![HTML](https://img.shields.io/badge/HTML-CSS-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![Status](https://img.shields.io/badge/Status-Active-2E7D32?style=for-the-badge)
![Contributors](https://img.shields.io/badge/Contributors-4-0097A7?style=for-the-badge&logo=github&logoColor=white)

*A full-stack web-based hospital management system for managing patients, doctors,*
*consultant teams, wards, complaints, treatments, and analytical reporting.*

[Features](#features) · [Pages](#pages) · [Reports](#reports) · [Quick Start](#quick-start)

</div>

---

## Overview

**Ivor Paine Memorial Hospital** is a web-based hospital management system built with PHP and Microsoft SQL Server. The system manages the complete operational data of a hospital — patients, doctors, consultants, wards, care units, nurses, complaints, and treatments — through a structured relational database and a clean eight-page web interface.

The dashboard serves as the central hub with live statistics across the entire hospital. Every section handles a dedicated area of operations, from patient admissions to staff management to analytical reporting.

> Built as a team project by 3 contributors as part of a Database Management Systems course at FAST NUCES Islamabad.

---

## Features

### Patient Management
- Admit patients with bed assignment and care unit placement
- Full medical profile per patient — complaints, treatments, and history
- Open and close treatment records with start and end dates

### Doctor and Staff Management
- Add doctors with position, care unit assignment, and automatic record creation
- Log previous employment history and periodic performance review grades
- Consultant promotion with specialty assignment and team management

### Ward Structure
- Hierarchical view from ward down to care unit, nurses, and patients
- Nurses categorized by type — DaySister, NightSister, NonReg
- Each care unit linked to its nurse in charge

### Complaints and Treatments
- Register complaint and treatment codes with descriptions
- Assign complaints and treatments to patients with date tracking
- Close out active treatments with a single form submission

### Analytical Reports
- 16 pre-built SQL queries covering the full range of hospital data
- Three parameterized queries accepting live user input — executed safely with bound parameters
- Results rendered dynamically with column headers extracted from the result set

---

## Pages

| Page | Description |
|------|-------------|
| Dashboard | Live counts — patients, doctors, consultants, nurses, active treatments, wards |
| Patients | Admissions, care unit assignment, treatment history |
| Doctors | Staff records, experience history, performance reviews |
| Consultants | Specialty assignment, team membership management |
| Wards | Hierarchical ward and care unit browser |
| Complaints | Complaint registration and patient assignment |
| Treatments | Treatment management and discharge |
| Reports | 16 analytical SQL queries with live input support |

---

## Reports

The reports page contains 16 pre-built queries covering:

- Consultants with their full doctor teams
- Ward structure showing care units and nurses in charge
- All patients with their complaints, treatments, and dates
- Junior doctors linked to their patients and care-unit nurses
- Consultants holding a unique specialty
- Complaint and treatment records cross-referenced with doctor experience
- Patients with more than one registered complaint
- Patients grouped by complaint and treatment combination
- Full performance review history for a specific doctor
- Complete medical profile for a specific patient
- Treatments given for a specific complaint within a date range
- Staff position summary with counts
- All currently ongoing treatments
- Doctors whose care unit has no admitted patients
- Most common complaints ranked by patient count
- All performance grades per doctor

---

## Quick Start

```bash
git clone https://github.com/bilalxfaisal/Hospital-System.git
cd Hospital-System
```

Run the schema against your SQL Server instance:

```bash
sqlcmd -S your_server -i schema.sql
```

Edit `db.php` with your SQL Server credentials:

```php
$serverName        = "YOUR_SERVER\\SQLEXPRESS";
$connectionOptions = [
    "Database" => "ipmhDB",
    "Uid"      => "",
    "PWD"      => ""
];
```

Place the project in your web server root and open `index.php` in your browser.

---

## Built With

- **[PHP 7.4+](https://www.php.net/)** — Backend with sqlsrv extension
- **[Microsoft SQL Server](https://www.microsoft.com/en-us/sql-server)** — Relational database
- **HTML and CSS** — Frontend with no external frameworks or libraries

---

## LinkedIn

https://www.linkedin.com/posts/bilal-faisal-6b7b7b328_dbms-databasedesign-database-activity-7462176695224016896-79lh/

---

<div align="center">

*Built as a collaborative team project demonstrating full-stack database application development,*
*relational schema design, and analytical SQL.*

</div>
