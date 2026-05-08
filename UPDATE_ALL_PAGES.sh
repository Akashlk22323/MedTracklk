#!/bin/bash
# Script to update all pages to glassmorphism design

echo "Updating all pages to glassmorphism design..."

# Function to update a PHP file
update_file() {
    local file=$1
    
    # Skip if file doesn't exist
    [ ! -f "$file" ] && return
    
    # Replace old classes with new glass classes
    sed -i 's/dashboard-container/container-fluid/g' "$file"
    sed -i 's/class="sidebar"/class="sidebar-glass"/g' "$file"
    sed -i 's/class="navbar"/class="navbar navbar-glass"/g' "$file"
    sed -i 's/class="card"/class="card-glass"/g' "$file"
    sed -i 's/class="stat-card"/class="stat-card-glass"/g' "$file"
    sed -i 's/class="btn btn-primary"/class="btn btn-glass-primary"/g' "$file"
    sed -i 's/class="btn btn-secondary"/class="btn btn-glass"/g' "$file"
    sed -i 's/class="main-content"/class="col-lg-10 col-md-9 p-4"/g' "$file"
    sed -i 's/neu-shadow/glass/g' "$file"
    
    # Add Bootstrap classes if sidebar exists
    if grep -q "sidebar-glass" "$file"; then
        sed -i 's/<aside class="sidebar-glass">/<div class="col-lg-2 col-md-3 p-0"><div class="sidebar-glass">/g' "$file"
        sed -i 's/<\/aside>/<\/div><\/div>/g' "$file"
    fi
    
    # Add row wrapper if needed
    if grep -q "sidebar-glass" "$file" && ! grep -q '<div class="row">' "$file"; then
        sed -i '/<div class="container-fluid">/a <div class="row">' "$file"
    fi
    
    echo "✓ Updated: $file"
}

# Update all patient pages
for file in /home/claude/doctor-appointment-system/patient/*.php; do
    update_file "$file"
done

# Update all doctor pages
for file in /home/claude/doctor-appointment-system/doctor/*.php; do
    update_file "$file"
done

# Update all admin pages
for file in /home/claude/doctor-appointment-system/admin/*.php; do
    update_file "$file"
done

# Update all pharmacist pages
for file in /home/claude/doctor-appointment-system/pharmacist/*.php; do
    update_file "$file"
done

echo "All pages updated!"
