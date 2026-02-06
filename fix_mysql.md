# Fix MySQL Crash in XAMPP

## Quick Solutions (Try in order)

### Solution 1: Use Alternative Port (3307)
1. Open XAMPP Control Panel
2. Click **Config** button next to MySQL
3. Select **my.ini**
4. Find the line: `port=3306`
5. Change it to: `port=3307`
6. Save and close
7. Start MySQL again

### Solution 2: Check for Port Conflicts
Another program might be using port 3306. Common culprits:
- **SQL Server** (Microsoft)
- **PostgreSQL**
- **Another MySQL instance**

**To check:**
```powershell
netstat -ano | findstr :3306
```

**To stop conflicting service:**
```powershell
# If SQL Server is running:
net stop MSSQLSERVER

# Or check Windows Services (services.msc)
```

### Solution 3: Repair MySQL Data Directory
1. **Backup your data first!**
2. Stop MySQL in XAMPP
3. Rename folder: `C:\xampp\mysql\data` to `C:\xampp\mysql\data_old`
4. Copy folder: `C:\xampp\mysql\backup` to `C:\xampp\mysql\data`
5. Start MySQL

### Solution 4: Reset MySQL (Last Resort)
⚠️ **This will delete all databases!**

1. Stop MySQL
2. Delete: `C:\xampp\mysql\data` folder
3. Copy: `C:\xampp\mysql\backup` to `C:\xampp\mysql\data`
4. Start MySQL
5. Recreate your database

### Solution 5: Use SQLite Instead (Temporary)
If MySQL won't start, we can use SQLite for development:

1. Update `.env`:
```
DB_CONNECTION=sqlite
DB_DATABASE=database/auction_portal.sqlite
```

2. Create SQLite database:
```bash
php artisan migrate
```

## Recommended: Use Docker MySQL

For a more reliable setup:

```bash
docker run -d \
  --name auction-mysql \
  -p 3306:3306 \
  -e MYSQL_ROOT_PASSWORD=root \
  -e MYSQL_DATABASE=auction_portal \
  mysql:8.0
```

## Current Configuration

Your `.env` is now set to use port **3307**.

Try starting MySQL in XAMPP again. If it still fails, try Solution 2 or 3.
