# YCAM Mentorship Program Student Portal

A comprehensive WordPress plugin for managing college application tracking, student progress, document management, and mentorship program administration.

## Version
3.0.0

## Description
The YCAM Mentorship Program Student Portal is a complete solution for managing a college preparation mentorship program. It provides separate interfaces for students and administrators, with features for tracking roadmap progress, managing documents, scheduling coach sessions, and facilitating communication.

## Features

### Student Portal
- **College Application Roadmap** - 10-step guided process with progress tracking
- **Document Management** - Upload and track application documents
- **Community Service Letter** - Approval workflow for 50-hour completion letters
- **One-on-One Coach Sessions** - View scheduled sessions with career coach
- **Weekly Attendance Tracking** - Monitor program participation
- **Challenge System** - Gamified progress tracking linked to roadmap steps
- **Dual Messaging System**:
  - Email to College & Career Coach
  - Internal chat with Program Admin
- **Resources & Tools** - Links to GAFutures, GSFC, FAFSA, and college prep vocabulary
- **Calendly Integration** - Schedule sessions directly in portal

### Admin Portal
- **Student Management** - View all students with progress percentages
- **Student Drill-Down** - Complete profile with roadmap status, attendance, documents
- **Attendance Management** - Track weekly program attendance
- **Coach Sessions** - Schedule and manage one-on-one sessions
- **Document Approval** - Review and approve student documents
- **Community Service Letters** - Upload and approve completion letters
- **Challenge Management** - Create challenges linked to roadmap steps
- **Messaging Center** - Two-way communication with students
- **Coach Messages** - View messages sent to career coach
- **Advisor Settings** - Customize coach information and stats

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The plugin will automatically:
   - Create all required database tables
   - Create default pages (Student Registration, Student Login, Student Dashboard)
   - Set up default settings

## Required Pages

The plugin automatically creates these pages on activation:
- `/student-registration/` - Student registration form
- `/student-login/` - Student login page
- `/student-dashboard/` - Student portal dashboard

## Admin Access

Access the admin portal at: `yoursite.com/portal-admin/`

## Database Tables

The plugin creates 11 tables:
- `tfsp_students` - Student profiles with parent information
- `tfsp_documents` - Document uploads with approval workflow
- `tfsp_student_progress` - Roadmap progress tracking
- `tfsp_attendance_records` - Weekly attendance
- `tfsp_coach_sessions` - One-on-one session tracking
- `tfsp_messages` - Messaging system
- `tfsp_challenges` - Challenge management
- `tfsp_recommendations` - Recommendation letter tracking
- `tfsp_upcoming_sessions` - Advisor session scheduling
- `tfsp_resources` - Resource management
- `tfsp_settings` - Portal settings

## Student Registration Fields

The registration form captures:
- Student name and email
- Student phone number
- Parent name, email, and phone
- Classification (Freshman/Sophomore/Junior/Senior)
- Shirt size
- Blazer size

## Roadmap Steps

1. Academic Resume
2. Personal Essay
3. Recommendation Letters
4. Transcript
5. Financial Aid
6. Community Service
7. Create Interest List of Colleges
8. College Tours
9. FAFSA
10. College Admissions Tests

## Community Service Letter Workflow

1. Admin uploads letter for student (status: Pending)
2. Letter is invisible to student until approved
3. Ms. Parkman reviews and approves letter
4. Student sees banner with download button
5. Provides auditable approval process

## Messaging System

**Two Separate Paths:**
1. **Coach Messages** - Sent via email to career coach, logged in admin portal
2. **Portal Messages** - Internal chat between student and program admin

## Customization

### Advisor Settings
Navigate to Admin Portal → Advisor Settings to customize:
- Section title
- Tagline
- Statistics (Students Helped, Success Rate)
- Meeting link (Calendly URL)
- Recommendation form link
- Recommendation email

### Calendly Integration
Update the Calendly URL in `templates/student-dashboard-exact.php` line 1133:
```php
data-url="YOUR_CALENDLY_URL"
```

## Technical Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## File Upload Security

The plugin creates a secure upload directory at `/wp-uploads/tfsp-documents/` with:
- `.htaccess` file to prevent direct access
- Index browsing disabled
- PHP execution blocked

## Support

For issues or questions, contact the development team.

## Changelog

### Version 3.0.0
- Complete rewrite with modern architecture
- Added community service letter approval workflow
- Implemented one-on-one coach session tracking
- Added dual messaging system (coach email + admin chat)
- Mobile responsive design
- Automatic database table creation on activation
- Enhanced student registration with parent information
- Calendly integration for session scheduling
- Challenge system linked to roadmap progress
- Weekly attendance tracking
- Document approval workflow
- Student drill-down with complete profile view

## Credits

Developed for Turner Foundation / YCAM Mentorship Program

## License

Proprietary - All rights reserved
