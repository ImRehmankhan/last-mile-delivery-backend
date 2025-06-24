<h1 align="center">🔧 Last Mile Delivery – Backend (Laravel API)</h1>

<p align="center">
  <b>Laravel REST API for a multi-role delivery system</b>  
  <br/>
  Powering customer, vendor, and rider operations for the Last Mile Delivery app
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-Backend-red?style=flat-square&logo=laravel" />
  <img src="https://img.shields.io/badge/API-RESTful-orange?style=flat-square" />
  <img src="https://img.shields.io/badge/Status-Active-brightgreen?style=flat-square" />
</p>

---

## 📦 Project Overview

This is the **backend REST API** built using **Laravel** for the [Last Mile Delivery System](https://github.com/ImRehmankhan/Last_Mile_Delivery).  
It handles authentication, order processing, vendor/rider onboarding, branch management, and courier logistics.

---

## 🔑 Core Features

- ✅ Multi-role authentication: Customer, Vendor, Rider, Admin
- 🛒 Order creation, update, tracking
- 🧑‍🍳 Vendor onboarding (manual + API-based)
- 🧾 Suborder and branch management
- 🗺 Distance-based pricing via Haversine formula
- 📦 Courier parcel handling
- 📊 Rider earnings & logs
- 📮 Notification logic for order status updates

---

## 📂 API Endpoints (Highlights)

> Full endpoint documentation is available in Postman or Swagger (coming soon)

| Endpoint                        | Description                             |
|----------------------------------|-----------------------------------------|
| `POST /api/login`                | Authenticate user (role-based)          |
| `GET /api/orders`                | Fetch orders for vendor or customer     |
| `POST /api/orders`              | Create a new order                      |
| `POST /api/courier/send`        | Send parcel request                     |
| `GET /api/rider/jobs`           | Rider: view available deliveries        |
| `PUT /api/order/status/{id}`    | Update order status (Admin/Rider)       |

---

## ⚙️ Installation & Setup

> Make sure PHP, Composer, and MySQL are installed on your system

```bash
# Clone the repo
git clone https://github.com/ImRehmankhan/Last_Mile_Delivery.git

# Navigate to backend folder
cd Last_Mile_Delivery/backend

# Install dependencies
composer install

# Create environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Configure .env with your DB credentials

# Run database migrations
php artisan migrate

# Start local development server
php artisan serve
```
🧪 Testing
Use Postman to test the API endpoints. You can create separate collections for:

Customer flow (register, place order, track order)

Vendor flow (receive orders, manage shop)

Rider flow (accept jobs, update status)

Admin flow (approve vendors, manage platform)

🔐 Authentication
API uses Laravel Sanctum or token-based authentication (depending on implementation)

Ensure headers are passed correctly with each request

👨‍💻 Developed By
Muhammad Rehman
Backend: Laravel | Frontend: React Native
📧 rehmanattock30@gmail.com
🔗 GitHub Profile
🔗 Project Repo

📄 License
This project is open-source and available under the MIT License.

<p align="center"> Built with ❤️ using Laravel – Ready for production APIs 🚀 </p>
