# Ivor Paine Memorial Hospital — Management System

A PHP-based hospital management system built on SQL Server. Manages patients, doctors, consultant teams, wards, complaints, and treatments through a web interface.

## Pages

| Page | Description |
|---|---|
| Dashboard | Overview stats and quick actions |
| Patients | Admit patients, assign complaints and treatments |
| Doctors | Add doctors, log experience and performance reviews |
| Consultants | Manage consultant teams and specialties |
| Wards | Browse wards, care units, nurses and assigned patients |
| Complaints | Register complaints and assign to patients |
| Treatments | Add treatments and close out active ones |
| Reports | 16 pre-built queries for analytical reporting |

## Stack

- **Backend:** PHP with `sqlsrv` extension
- **Database:** Microsoft SQL Server (`ipmhDB`)
- **Frontend:** Vanilla HTML/CSS (no frameworks)

## Setup

1. Clone the repo and place files in your web server root (e.g. `htdocs/` or `www/`)
2. Make sure the SQL Server PHP driver (`sqlsrv`) is installed and enabled
3. Create the `ipmhDB` database and run your schema/seed scripts
4. Edit `db.php` to match your SQL Server host and credentials
5. Open `index.php` in your browser

## Requirements

- PHP 7.4+ with `sqlsrv` extension
- Microsoft SQL Server (local or remote)
- A web server (Apache / IIS / PHP built-in server)