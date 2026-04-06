# Implementation Plan: Seller Dashboard Pulse App

## Objective
Create a dedicated Flutter application for the Seller side (`SellerApp`), matching the design provided in `stitch_seller_dashboard_pulse`. Update the backend to support the advanced analytics and activity feed required by the design.

## Phase 1: Backend Implementation (Data Support)
The current backend returns basic stats. We need to enhance `SellerController` and `SellerService` to provide:
1.  **Enhanced Stats**:
    -   Growth percentage (requires historical comparison).
    -   "Closing soon" count for active bids.
    -   "This month" filter for sold items.
2.  **Performance Insights (Chart Data)**:
    -   Sales breakdown by category (Watches, Fine Art, etc.).
3.  **Recent Activity Feed**:
    -   New bids, items sold, new listings, etc.

### Tasks
-   [ ] Update `SellerService::getSellerStats` to include growth metrics and category breakdown.
-   [ ] Create `SellerService::getRecentActivity` to fetch recent events.
-   [ ] Expose these via `SellerController`.

## Phase 2: Flutter App Setup
-   [ ] Initialize a new Flutter project: `SellerApp` (or update existing `BidOrbit` with seller module). *Decision: Create separate `SellerApp` for clarity unless user specifies otherwise.*
-   [ ] Configure theme and assets based on `guide.txt` / design tokens (Colors: Primary `#2977f5`, Background Dark `#0a0e17`, etc.).
-   [ ] specific dependencies: `fl_chart` (for charts), `flutter_svg`, `provider`, `http`.

## Phase 3: UI Implementation (Dashboard Pulse)
-   [ ] **Header**: User profile, Notifications.
-   [ ] **KPI Grid**: 
    -   Earnings Card (with sparkline - simplified initially).
    -   Active Bids Card.
    -   Sold Items Card.
-   [ ] **Performance Insights**: Doughnut Chart using `fl_chart`.
-   [ ] **Recent Activity**: List using `ListView.builder`.
-   [ ] **Bottom Navigation**: Pulse, Inventory, Messages, Settings.

## Phase 4: Integration
-   [ ] Connect Flutter app to Backend API.
-   [ ] Implement Authentication flow (Login as Seller).
-   [ ] Bind Dashboard data to real API responses.

## Current Status Analysis
-   **Backend**: 70% ready. Core data exists, but aggregation/analytics logic is missing.
-   **Design**: clearly defined in HTML/PNG.
-   **Frontend**: Not started.

## Recommendation
Proceed with Phase 1 and Phase 2 in parallel.
