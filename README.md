# Ivor Paine Memorial Hospital — Management System

A web-based hospital management system for managing patients, doctors, consultant teams, wards, care units, nurses, complaints, and treatments. Built with PHP and Microsoft SQL Server, with no external frontend frameworks.

---

## Pages

The application has eight pages accessible through a persistent top navigation bar.

**Dashboard** (index.php)
The landing page. Shows live counts for patients, doctors, consultants, nurses, active treatments, wards, and care units. Also displays the five most recently admitted patients and the five most recently started ongoing treatments, plus a quick-actions panel with links to common tasks and a summary of all consultant teams and their sizes.

**Patients** (patients.php)
Admit new patients by assigning them a patient number, name, date of birth, bed number, admission date, and care unit. View a full detail page per patient showing their ward, care unit, attending doctor, and their complete treatment history across all complaints. Assign a complaint and treatment to an existing patient with start and optional end dates.

**Doctors** (doctors.php)
Add new doctors with name, position, date joined, and care unit assignment. A RECORD entry is automatically created for each new doctor. From each doctor's detail page you can log previous experience entries (employer, role, dates) and add performance review records with a grade. The detail page also shows whether the doctor is a consultant, their specialty if so, and which consultant they report to if not.

**Consultants** (consultants.php)
Promote an existing doctor to consultant status by assigning a specialty. Add any non-consultant doctor to a consultant's team. The page shows all consultant teams with their members, specialties, and ward assignments.

**Wards** (wards.php)
Add new wards with a name and specialty. Select any ward from the list to see a full breakdown of its nurses grouped by type (DaySister, NightSister, NonReg), all currently admitted patients with their bed numbers and care unit, and all doctors assigned to care units within that ward.

**Complaints** (complaints.php)
Register new complaint codes with a description. Assign an existing complaint to a patient alongside a treatment and start date, directly creating a treatment record. Shows all complaints with a count of how many patients have received treatment for each.

**Treatments** (treatments.php)
Add new treatment codes with a description. View all treatments with a count of total uses. Displays all currently active (ongoing) treatments and provides a form to close out any of them by setting an end date.

**Reports** (queries.php)
Contains 16 pre-built analytical queries listed in a sidebar. Selecting a query runs it against the live database and displays the results in a table. Three queries accept user input — a doctor number, a patient number, and a complaint code with a date range — and are executed as parameterized queries. The remaining thirteen run immediately on page load.

The queries cover:

- Consultants with their full doctor teams
- Ward structure showing care units and nurses in charge
- All patients with their complaints, treatments, and dates
- Junior doctors linked to their patients and care-unit nurses
- Consultants holding a unique specialty
- Complaint and treatment records cross-referenced with the in-charge doctor's previous experience
- Patients with more than one registered complaint
- Patients grouped by complaint and treatment combination
- Full performance review history for a specific doctor
- Complete medical profile for a specific patient
- Treatments given for a specific complaint within a date range
- Staff position summary with counts
- All currently ongoing (undischarged) treatments
- Doctors whose care unit has no admitted patients
- Most common complaints ranked by patient count
- All performance grades per doctor

---
## Project Video
Watch the demo : https://youtu.be/pmVRtYd5EH8

---
## Tech Stack

- Backend: PHP with the sqlsrv extension
- Database: Microsoft SQL Server (database name: ipmhDB)
- Frontend: Plain HTML and custom CSS, no external frameworks or libraries

---

## Project Structure

```
hospital_system/
    index.php          Dashboard with live stats and quick actions
    patients.php       Patient admission and treatment assignment
    doctors.php        Doctor records, experience, and performance reviews
    consultants.php    Consultant promotion and team management
    wards.php          Ward and care unit browser
    complaints.php     Complaint registration and assignment
    treatments.php     Treatment management and discharge
    queries.php        16 analytical reports
    db.php             SQL Server connection and shared helpers
    nav.php            Shared navigation header included by all pages
    style.css          Application-wide stylesheet
    schema.sql         Full database schema with seed data
```

---

## Setup

1. Install PHP 7.4 or later with the sqlsrv extension enabled.

2. Place the project files in your web server root (for example htdocs for Apache or www for IIS).

3. Create the database and load the schema:

```
sqlcmd -S your_server -i schema.sql
```

Or run schema.sql through SQL Server Management Studio.

4. Edit db.php to match your SQL Server instance name and credentials:

```php
$serverName       = "YOUR_SERVER\\SQLEXPRESS";
$connectionOptions = [
    "Database" => "ipmhDB",
    "Uid"      => "",
    "PWD"      => ""
];
```

Windows Authentication (blank Uid and PWD) is used by default.

5. Open index.php in your browser to reach the dashboard.

---

## Requirements

- PHP 7.4 or later
- PHP sqlsrv and pdo_sqlsrv extensions
- Microsoft SQL Server (local or remote)
- A web server such as Apache, IIS, or the PHP built-in development server

---

## LinkedIn

https://www.linkedin.com/posts/bilal-faisal-6b7b7b328_dbms-databasedesign-database-ugcPost-7462176555277135872-rnmz/
