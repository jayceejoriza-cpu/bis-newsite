# Quick Start Guide

## 🚀 How to Use This Template

### Step 1: Open the Template
Simply open `index.html` in your web browser by:
- Double-clicking the `index.html` file, OR
- Right-click → Open with → Your preferred browser, OR
- Drag and drop `index.html` into your browser window

### Step 2: View the Dashboard
You should see:
- ✅ A sidebar with navigation menu
- ✅ Header with date/time and user profile
- ✅ Three statistics cards (Residents, Households, Requests)
- ✅ Population Growth chart
- ✅ Blotter Records chart
- ✅ Age Demographics pie chart

### Step 3: Test Responsiveness
- Resize your browser window to see responsive design
- On mobile view, click the hamburger menu (☰) to toggle sidebar
- All charts should resize automatically

## 📝 Customization Steps

### Change the Barangay Name
**File**: `index.html` (Line 48)
```html
<h2 class="sidebar-title">Your Barangay Name</h2>
```

### Update Statistics
**File**: `index.html` (Lines 130-165)
```html
<h3 class="stat-value">16,798</h3> <!-- Change this number -->
<p class="stat-label">Total Residents</p> <!-- Change this label -->
```

### Modify Chart Data
**File**: `js/script.js`

**Population Chart** (Line 60):
```javascript
const populationData = years.map((year, index) => {
    // Modify this formula or replace with your actual data
    return yourDataArray[index];
});
```

**Blotter Records** (Line 120):
```javascript
const blotterData = {
    pending: [0.5, 0.3, 0.7, ...], // Your monthly data
    underInvestigation: [0.8, 0.6, 1.0, ...],
    // etc.
};
```

**Age Demographics** (Line 200):
```javascript
data: [30, 18, 35, 17], // Percentages for each age group
```

### Change Colors
**File**: `css/style.css` (Lines 11-20)
```css
:root {
    --primary-color: #3b82f6;     /* Main blue color */
    --secondary-color: #10b981;   /* Green color */
    --accent-color: #f59e0b;      /* Orange color */
    --danger-color: #ef4444;      /* Red color */
}
```

## 🎨 Color Schemes

### Blue Theme (Default)
```css
--primary-color: #3b82f6;
--secondary-color: #10b981;
```

### Purple Theme
```css
--primary-color: #8b5cf6;
--secondary-color: #ec4899;
```

### Green Theme
```css
--primary-color: #10b981;
--secondary-color: #06b6d4;
```

## 📱 Mobile Testing

1. Open browser DevTools (F12)
2. Click the device toolbar icon (Ctrl+Shift+M)
3. Select different device sizes
4. Test the mobile menu functionality

## 🔧 Common Issues & Solutions

### Charts Not Showing?
- Check browser console (F12) for errors
- Ensure internet connection (Chart.js loads from CDN)
- Verify all files are in correct folders

### Sidebar Not Working on Mobile?
- Check if JavaScript is enabled
- Clear browser cache
- Verify `js/script.js` is loaded correctly

### Styling Issues?
- Ensure `css/style.css` path is correct
- Check for CSS syntax errors
- Clear browser cache (Ctrl+F5)

## 📊 Adding New Charts

1. Add canvas element in `index.html`:
```html
<canvas id="myNewChart"></canvas>
```

2. Initialize chart in `js/script.js`:
```javascript
const myNewChart = new Chart(ctx, {
    type: 'bar', // or 'line', 'pie', etc.
    data: { /* your data */ },
    options: { /* your options */ }
});
```

## 🎯 Next Steps

1. **Replace dummy data** with real data from your database
2. **Add backend integration** (PHP, Node.js, etc.)
3. **Implement authentication** for user login
4. **Add CRUD operations** for managing records
5. **Deploy to web server** for production use

## 📚 Resources

- [Chart.js Documentation](https://www.chartjs.org/docs/)
- [Font Awesome Icons](https://fontawesome.com/icons)
- [CSS Grid Guide](https://css-tricks.com/snippets/css/complete-guide-grid/)
- [Flexbox Guide](https://css-tricks.com/snippets/css/a-guide-to-flexbox/)

## 💡 Tips

- Use browser DevTools to inspect and modify styles in real-time
- Test on multiple browsers for compatibility
- Keep backups before making major changes
- Comment your code for future reference

## ✅ Checklist

- [ ] Template opens in browser
- [ ] All charts display correctly
- [ ] Sidebar navigation works
- [ ] Mobile menu toggles properly
- [ ] Date/time updates every second
- [ ] Hover effects work on cards
- [ ] Responsive design works on mobile

---

**Need Help?** Check the main README.md for detailed documentation.

**Ready to Deploy?** Upload all files to your web server maintaining the folder structure.
