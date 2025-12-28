## Members
Rigene Curimao
Caren Hate
Carol Hate

## Sample accounts
admin 
username: zap password: 123
Tenants
username: sample password: Sample0



# Boarding House Management System (BHMS)

A comprehensive web-based system for managing boarding house operations, including tenant management, room allocation, payment tracking, and administrative oversight.

## 🚀 Features

### Admin Dashboard
- **Business Intelligence**: Real-time revenue metrics, occupancy rates, and financial analytics
- **Interactive Charts**: Revenue history, room status distribution, collection progress, and pricing analytics
- **Export Reports**: Print-ready business reports with key performance indicators

### Room Management
- **Room Inventory**: Add, edit, and manage room units with pricing and status tracking
- **Status Management**: Track room availability (available, occupied, maintenance)
- **Maintenance Mode**: Set rooms under maintenance to prevent assignments

### Tenant Management
- **Tenant Directory**: Comprehensive tenant database with contact information and room assignments
- **Room Assignment**: Assign tenants to available rooms with automatic status updates
- **Time Tracking**: Record tenant check-in/check-out times for stay duration monitoring
- **Data Export**: Export tenant data in Excel, PDF, or print formats

### Payment & Billing System
- **Payment Tracking**: Record and manage monthly rent payments with detailed history
- **Receipt Generation**: Automatic receipt generation for payment confirmations
- **Unpaid Tracking**: Monitor tenants with outstanding balances
- **Advanced Filtering**: Filter payments by month, year, and tenant name
- **Revenue Analytics**: Monthly and year-to-date revenue tracking

### Tenant Portal
- **Self-Service Dashboard**: Tenants can view their account status and payment history
- **Room Selection**: Unassigned tenants can select available rooms
- **Online Payments**: Secure payment processing through GCash and Maya
- **Bill Management**: View current and historical billing information
- **Receipt Access**: Download payment receipts anytime

### Authentication & Security
- **Dual Access**: Separate login portals for administrators and tenants
- **Secure Registration**: Tenant self-registration with email verification
- **Session Management**: Secure session handling with automatic logout
- **Password Security**: Hashed passwords with strength requirements

## 🛠️ Technologies Used

### Backend
- **PHP 7+**: Server-side scripting and business logic
- **MySQL**: Relational database for data storage
- **Prepared Statements**: SQL injection prevention

### Frontend
- **Bootstrap 5**: Responsive design framework
- **HTML5 & CSS3**: Modern markup and styling
- **JavaScript**: Interactive user interface elements

### Libraries & Tools
- **DataTables**: Advanced table management with search, sort, and pagination
- **Chart.js**: Interactive data visualization
- **Font Awesome**: Icon library for UI elements
- **AOS (Animate On Scroll)**: Smooth scroll animations
- **jQuery**: DOM manipulation and AJAX requests

## 📊 Database Schema

### Admin Table
```sql
CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255)
);
```

### Tenants Table
```sql
CREATE TABLE tenants (
    tenant_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    phone VARCHAR(20),
    room_id INT,
    time_in DATETIME,
    time_out DATETIME NULL,
    FOREIGN KEY (room_id) REFERENCES rooms(room_id)
);
```

### Rooms Table
```sql
CREATE TABLE rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10),
    price DECIMAL(10,2),
    status ENUM('available', 'occupied', 'maintenance')
);
```

### Payments Table
```sql
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT,
    amount DECIMAL(10,2),
    payment_date DATETIME,
    payment_method VARCHAR(50),
    FOREIGN KEY (tenant_id) REFERENCES tenants(tenant_id)
);
```

## 🚀 Installation & Setup

### Prerequisites
- PHP 7.0 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx) or XAMPP/WAMP
- Modern web browser

### Installation Steps

1. **Clone the Repository**
   ```bash
   git clone https://github.com/yourusername/bhms.git
   cd bhms
   ```

2. **Database Setup**
   - Create a MySQL database named `boardinghouse_db`
   - Import the SQL schema from the Database Schema section above
   - Alternatively, run the provided SQL dump file

3. **Configuration**
   - Update database credentials in `config/db.php`:
   ```php
   $host = "localhost";
   $user = "your_username";
   $password = "your_password";
   $database = "boardinghouse_db";
   ```

4. **Web Server Setup**
   - Place the project in your web server's root directory
   - For XAMPP: Place in `htdocs/bhms/`
   - For WAMP: Place in `www/bhms/`

5. **Access the Application**
   - Open your browser and navigate to `http://localhost/bhms`
   - Admin login: Use default admin credentials (set in database)
   - Tenant registration: Available through the login page

## 📖 Usage Guide

### For Administrators

1. **Initial Setup**
   - Log in with admin credentials
   - Add rooms to your inventory with pricing
   - Set room statuses appropriately

2. **Daily Operations**
   - Monitor dashboard for key metrics
   - Manage tenant registrations and room assignments
   - Track payments and generate receipts
   - Export reports as needed

3. **Maintenance**
   - Set rooms to maintenance mode when needed
   - Update room pricing and information
   - Monitor occupancy rates and revenue trends

### For Tenants

1. **Getting Started**
   - Register through the tenant portal
   - Wait for admin approval and room assignment

2. **Account Management**
   - View assigned room and rental details
   - Monitor payment status and due dates
   - Access payment history and receipts

3. **Making Payments**
   - Use the tenant dashboard to initiate payments
   - Choose payment method (GCash/Maya)
   - Receive instant payment confirmation

## 🔧 API Endpoints

The system includes several AJAX endpoints for dynamic functionality:

- `payments/assign_room.php` - Room assignment for tenants
- `payments/payment_process.php` - Payment processing
- `payments/generate_receipt.php` - Receipt generation
- `tenants/process_tenant.php` - Tenant registration
- `rooms/add.php` - Room creation

## 🎨 Customization

### Styling
- CSS variables defined in `:root` for easy theme customization
- Bootstrap classes used throughout for consistent styling
- Responsive design breakpoints for mobile compatibility

### Features
- Modular PHP structure allows easy feature additions
- Database schema supports extension with additional fields
- Chart configurations can be modified in dashboard files

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

##  upport

For support and questions:
- Create an issue in the GitHub repository
- Check the documentation for common solutions
- Contact the development team

## 🔄 Version History

### v2.0 (Current)
- Modern UI redesign with Bootstrap 5
- Enhanced tenant portal with self-service features
- Advanced analytics dashboard
- Improved payment processing
- Mobile-responsive design

### v1.0
- Basic CRUD operations for tenants and rooms
- Simple payment tracking
- Admin- interface
- Tenants-interface
- Basic reporting features

---

**Built with for modern property management**
