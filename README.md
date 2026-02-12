# Barangay Management System - Dashboard Template

A modern, responsive dashboard template for Barangay Management Systems with interactive charts and analytics.

## Features

✨ **Modern Design**
- Clean and professional interface
- Responsive layout that works on all devices
- Smooth animations and transitions

📊 **Interactive Charts**
- Population Growth area chart
- Blotter Records multi-line chart
- Age Demographics doughnut chart
- Built with Chart.js for smooth interactions

🎨 **Customizable**
- Easy to modify colors and styles
- Modular CSS structure
- Well-commented code

📱 **Responsive**
- Mobile-friendly sidebar navigation
- Adaptive layouts for tablets and phones
- Touch-friendly interface

## File Structure

```
barangay-dashboard/
├── index.html          # Main HTML file
├── css/
│   └── style.css      # All styles and responsive design
├── js/
│   └── script.js      # JavaScript functionality and charts
└── README.md          # Documentation
```

## Technologies Used

- **HTML5** - Semantic markup
- **CSS3** - Modern styling with Flexbox and Grid
- **JavaScript (ES6+)** - Interactive functionality
- **Chart.js** - Data visualization
- **Font Awesome** - Icons
- **Google Fonts (Inter)** - Typography

## Getting Started

1. **Clone or download** this template
2. **Open** `index.html` in your web browser
3. **Customize** the content, colors, and data as needed

## Customization Guide

### Changing Colors

Edit the CSS variables in `css/style.css`:

```css
:root {
    --primary-color: #3b82f6;
    --secondary-color: #10b981;
    --accent-color: #f59e0b;
    /* ... more colors */
}
```

### Updating Chart Data

Modify the data arrays in `js/script.js`:

```javascript
// Population Chart
const populationData = [/* your data */];

// Blotter Records
const blotterData = {
    pending: [/* your data */],
    underInvestigation: [/* your data */],
    // ...
};

// Demographics
datasets: [{
    data: [30, 18, 35, 17], // Update percentages
}]
```

### Adding Menu Items

Add new navigation items in `index.html`:

```html
<li class="nav-item">
    <a href="#" class="nav-link">
        <i class="fas fa-icon-name"></i>
        <span>Menu Item</span>
    </a>
</li>
```

### Modifying Statistics Cards

Update the stat cards in `index.html`:

```html
<div class="stat-card">
    <div class="stat-content">
        <h3 class="stat-value">Your Value</h3>
        <p class="stat-label">Your Label</p>
    </div>
    <div class="stat-icon blue">
        <i class="fas fa-your-icon"></i>
    </div>
</div>
```

## Browser Support

- ✅ Chrome (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Edge (latest)
- ✅ Mobile browsers

## Features Breakdown

### Sidebar Navigation
- Collapsible menu items
- Active state indicators
- Smooth hover effects
- Mobile-responsive drawer

### Header
- Real-time date and time display
- Theme toggle button (ready for dark mode)
- User profile section

### Dashboard Cards
- Total Residents counter
- Total Households counter
- Pending Requests counter
- Animated hover effects

### Charts
1. **Population Growth** - Shows annual population trends
2. **Blotter Records** - Displays case status breakdown
3. **Age Demographics** - Visualizes population by age groups

## Performance

- Lightweight and fast loading
- Optimized chart rendering
- Smooth animations (60fps)
- Minimal dependencies

## Responsive Breakpoints

- **Desktop**: > 1200px
- **Tablet**: 768px - 1200px
- **Mobile**: < 768px

## Future Enhancements

- [ ] Dark mode implementation
- [ ] Data export functionality
- [ ] Print-friendly layouts
- [ ] Advanced filtering options
- [ ] Real-time data updates
- [ ] User authentication UI
- [ ] Settings panel

## License

This template is free to use for personal and commercial projects.

## Credits

- **Icons**: Font Awesome
- **Charts**: Chart.js
- **Fonts**: Google Fonts (Inter)

## Support

For questions or issues, please refer to the documentation or modify the code as needed.

---

**Version**: 1.0.0  
**Last Updated**: January 2025  
**Built with**: ❤️ for community management
