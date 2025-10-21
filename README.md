# GSB DATA - Mini ERP Project

A mini ERP project for Galaxy-Swiss Bourdin (GSB), a fictional pharmaceutical company. This project was developed as part of the BTS SIO (Services Informatiques aux Organisations) curriculum.

The live version of the project is hosted at: [https://gsb-nolan-liogier.fr/](https://gsb-nolan-liogier.fr/)

## Features

The project consists of two main applications: a Web Dashboard and a Mobile Field Application.

### Web Application (Dashboard)

A complete web-based enterprise management application with a robust role-based system.

*   **Comprehensive Management:** Integrated system for managing client companies, orders, stock, and users with differentiated roles (Admin, Commercial, Logistics, Client).
*   **Personalized Dashboards:** User-role-specific dashboards featuring real-time data visualization, statistics, and interactive graphs.
*   **Security & Workflow:** Secure authentication, a defined order validation workflow (Client → Commercial → Logistics), and role-based permission management.

### Mobile Application (Field)

A complete mobile application for field management, designed for GSB sales representatives.

*   **Field Planning:** Intelligent agenda system for planning client visits, including availability management and automatic commercial assignment.
*   **Feedback & Tracking:** Collection of post-visit client feedback, management of distributed samples, and real-time commercial performance tracking.
*   **Notifications & Communication:** Push notification system for appointments, availability alerts, and instant communication between commercial teams.

## Technologies Used

### Web Application (Dashboard)

*   **Backend:** PHP (MVC Architecture)
*   **Frontend Styling:** Tailwind CSS
*   **Database:** MariaDB

### Mobile Application (Field)

*   **Mobile Framework:** Flutter (Dart)
*   **Backend API:** Node.js
*   **Database:** PostgreSQL

## Installation and Setup

1.  Clone the repository: `git clone https://github.com/your-username/gsb-central-website.git`
2.  Install dependencies: `composer install`
3.  Configure your web server to point to the public directory.
4.  Import the database schema from `database.sql`.
5.  Update the database credentials in `config.php`.

## Usage

1.  Open the application in your web browser.
2.  Log in with your credentials.
3.  Use the dashboard to manage clients, orders, and other data.

## Author

*   **Nolan Liogier** - [https://github.com/nolanliogier](https://github.com/nolanliogier)

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
