# Design

## Overview

This overview of the code was provided by GPT4o.

This WordPress plugin, **"This Day In History"**, allows users to create, manage, and display historical or future events. It provides functionality through widgets, shortcodes, and admin interfaces. Here's an overview of how the plugin works:

---

### **1. Plugin Initialization**

- **File**: this-day-in-history.php
    - The plugin defines constants, such as `TDIH_DB_VERSION`, to track the database version.
    - It includes core files like tdih-init.class.php, tdih-widget.php, tdih-shortcodes.php, and tdih-list-table.class.php.
    - Hooks are registered for activation (`on_activate`), deactivation (`on_deactivate`), and database updates (`tdih_db_updates`).

---

### **2. Database Management**

- **File**: tdih-init.class.php
    - On activation, the plugin:
        - Adds default options (`tdih_options`) for date formats, era markers, and other settings.
        - Adds a custom capability (`manage_tdih_events`) to the administrator role.
    - On deactivation, it performs cleanup tasks (though minimal in this case).

- **File**: uninstall.php
    - On uninstallation, the plugin:
        - Deletes custom taxonomy terms (`event_type`).
        - Deletes all posts of type `tdih_event`.
        - Removes plugin options and custom capabilities.

---

### **3. Custom Post Type**

- **File**: this-day-in-history.php
    - The plugin registers a custom post type, `tdih_event`, to store historical events.
    - Events can be excluded from search results based on the `exclude_search` option.

---

### **4. Custom Taxonomy**

- **File**: this-day-in-history.php
    - A custom taxonomy, `event_type`, is registered to categorize events (e.g., "Birth", "Death", "Anniversary").
    - Admin columns for the taxonomy are customized to display event counts and links.

---

### **5. Admin Interface**

- **File**: tdih-list-table.class.php
    - The plugin uses a custom `WP_List_Table` to display, search, and manage events in the WordPress admin.
    - Features include:
        - Bulk actions (e.g., delete multiple events).
        - Sorting and filtering by event date, name, type, and modification date.
        - Adding, editing, and deleting events with validation.

- **File**: this-day-in-history.php
    - Adds a menu item in the WordPress admin for managing events.
    - Provides settings for customizing the plugin's behavior (e.g., date format, exclusion from search).

---

### **6. Widgets**

- **File**: tdih-widget.php
    - The plugin provides a widget to display events in the sidebar.
    - Widget options include:
        - Filtering by event type.
        - Showing event age, year, or type.
        - Limiting the number of events displayed.
        - Excluding specific event types.

---

### **7. Shortcodes**

- **File**: tdih-shortcodes.php
    - The plugin provides two shortcodes:
    1. `[tdih]`: Displays a list of events for the current day.
    2. `[tdih_tab]`: Displays a table of events with customizable attributes (e.g., date, type, period).
    - Shortcodes support attributes like `show_age`, `show_link`, `type`, `day`, `month`, `year`, and `max_rows`.

---

### **8. Frontend Display**

- **File**: default-single-tdih_event.php
    - Provides a default template for displaying single events if the theme doesn't include one.
    - Displays the event title, content, and an "Edit Event" link for users with the appropriate capability.

---

### **9. Database Queries**

- **Files**: tdih-list-table.class.php, tdih-widget.php, tdih-shortcodes.php
    - The plugin uses `$wpdb` to query the database for events.
    - Queries are optimized with `LIMIT` and avoid using `RAND()` for performance.
    - Input sanitization and escaping are applied to prevent SQL injection.

---

### **10. Localization**

- **File**: this-day-in-history.php
    - The plugin loads a text domain (`this-day-in-history`) for translations.
    - All user-facing strings are translatable using `__()` or `_e()`.

---

### **11. Styling**

- **File**: tdih.css
    - Provides basic CSS for styling the admin interface and frontend displays.

---

### **12. Admin Notices**

- **File**: this-day-in-history.php
    - Displays admin notices (e.g., "Event created", "Event updated") based on query parameters.

---

### **13. Help Tabs**

- **File**: this-day-in-history.php
    - Adds contextual help tabs to the admin interface, explaining how to use the plugin.

---

### **14. Security Measures**

- Nonces are used for form submissions to prevent CSRF attacks.
- User capabilities are checked before performing sensitive actions.
- Inputs are sanitized and outputs are escaped to prevent XSS and SQL injection.

---

### **15. Performance Optimizations**

- Avoids `RAND()` in SQL queries.
- Uses pagination for admin tables to handle large datasets efficiently.

---

This plugin is a comprehensive solution for managing and displaying historical events in WordPress, with a focus on flexibility, security, and performance. Let me know if you need further clarification!
