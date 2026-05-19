# Database Documentation

## Overview

This project uses Laravel migrations to define its database schema. The primary domain tables are:

- `users`
- `products`
- `sales`
- `forecasts`
- `insights`
- `recommendations`

There are also supporting tables used by Laravel application features:

- `password_reset_tokens`
- `sessions`

---

## Tables

### users

Fields:
- `id` (primary key)
- `name` (string)
- `email` (string, unique)
- `email_verified_at` (timestamp, nullable)
- `password` (string)
- `remember_token` (string, nullable)
- `role` (enum: `user`, `admin`, default `user`)
- `created_at` / `updated_at`

Relationships:
- One `user` can own many `products`
- One `user` can have many `insights`
- One `user` can have many `recommendations`


### password_reset_tokens

Fields:
- `email` (primary key)
- `token` (string)
- `created_at` (timestamp, nullable)

Purpose:
- Stores password reset tokens for user email addresses.


### sessions

Fields:
- `id` (primary key)
- `user_id` (foreign key, nullable)
- `ip_address` (string, nullable)
- `user_agent` (text, nullable)
- `payload` (longText)
- `last_activity` (integer)

Purpose:
- Tracks authenticated session data for Laravel session storage.


### products

Fields:
- `id` (primary key)
- `user_id` (foreign key -> `users.id`, cascade on delete)
- `name` (string)
- `category` (string)
- `created_at` / `updated_at`

Relationships:
- One `product` belongs to a `user`
- One `product` has many `sales`
- One `product` has many `forecasts`
- One `product` may appear in many `recommendations`


### sales

Fields:
- `id` (primary key)
- `product_id` (foreign key -> `products.id`, cascade on delete)
- `region` (string)
- `date` (date)
- `quantity` (integer)
- `revenue` (decimal(10,2))
- `created_at` / `updated_at`

Relationships:
- One `sale` belongs to a `product`

Purpose:
- Stores actual historical sales performance for each product by region and date.


### forecasts

Fields:
- `id` (primary key)
- `product_id` (foreign key -> `products.id`, cascade on delete)
- `region` (string, nullable)
- `date` (date)
- `predicted_revenue` (decimal(10,2))
- `model_version` (string)
- `status` (enum: `pending`, `processing`, `completed`, `failed`, default `pending`)
- `input_data` (json, nullable)
- `result` (json, nullable)
- `started_at` (timestamp, nullable)
- `completed_at` (timestamp, nullable)
- `job_id` (string, nullable)
- `created_at` / `updated_at`

Relationships:
- One `forecast` belongs to a `product`

Purpose:
- Stores AI forecast predictions, lifecycle status, and execution metadata.


### insights

Fields:
- `id` (primary key)
- `user_id` (foreign key -> `users.id`, cascade on delete)
- `type` (string)
- `title` (string)
- `description` (text)
- `status` (enum: `pending`, `processing`, `completed`, `failed`, default `pending`)
- `input_data` (json, nullable)
- `result` (json, nullable)
- `model_version` (string, nullable)
- `started_at` (timestamp, nullable)
- `completed_at` (timestamp, nullable)
- `job_id` (string, nullable)
- `created_at` / `updated_at`

Relationships:
- One `insight` belongs to a `user`

Purpose:
- Tracks generated AI insights, their processing state, and audit data.


### recommendations

Fields:
- `id` (primary key)
- `user_id` (foreign key -> `users.id`, cascade on delete)
- `product_id` (foreign key -> `products.id`, nullable, cascade on delete)
- `message` (text)
- `priority` (string)
- `status` (enum: `pending`, `processing`, `completed`, `failed`, default `pending`)
- `input_data` (json, nullable)
- `result` (json, nullable)
- `model_version` (string, nullable)
- `started_at` (timestamp, nullable)
- `completed_at` (timestamp, nullable)
- `job_id` (string, nullable)
- `created_at` / `updated_at`

Relationships:
- One `recommendation` belongs to a `user`
- One `recommendation` may belong to a `product`

Purpose:
- Stores AI recommendation messages and metadata for product-related or user-specific guidance.

---

## Key relationships summary

- `users.id` -> `products.user_id`
- `users.id` -> `insights.user_id`
- `users.id` -> `recommendations.user_id`
- `products.id` -> `sales.product_id`
- `products.id` -> `forecasts.product_id`
- `products.id` -> `recommendations.product_id`

## Notes

- Cascade delete is enabled on all foreign keys, so deleting a `user` removes their products, insights, and recommendations; deleting a `product` removes its sales, forecasts, and related product-linked recommendations.
- AI-related tables use `status`, `input_data`, `result`, `model_version`, `started_at`, `completed_at`, and `job_id` fields to track async processing and model output.
- `revenue` fields are stored with precision `10,2`.
- `region` is used in both `sales` and `forecasts` for geographic segmentation.
