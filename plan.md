# YCAM Mentorship Program Portal - Implementation Plan
**Date Created:** October 16, 2025  
**Last Updated:** October 16, 2025

## High-Priority Functionality Overhaul

### 1. Automate One-on-One Coaching Session Tracking
- [x] **Research Calendly API Integration**
  - [x] Study Calendly webhook capabilities
  - [x] Determine authentication requirements
  - [x] Assess data synchronization options
- [x] **Feasibility & Cost Study**
  - [x] Document technical requirements
  - [x] Estimate development time
  - [x] Identify potential challenges
  - [x] Provide cost breakdown
- [x] **Database Schema Updates**
  - [x] Modify `tfsp_coach_sessions` table for Calendly integration
  - [x] Add fields for Calendly event ID, webhook data
  - [x] Create `tfsp_calendly_settings` table
- [x] **Webhook Implementation**
  - [x] Create webhook endpoint to receive Calendly events
  - [x] Handle appointment creation, updates, cancellations
  - [x] Auto-populate sessions in student and admin portals
- [x] **Admin Status Update Interface**
  - [x] Create Calendly settings configuration page
  - [x] Add webhook URL and API token management
  - [x] Enable/disable integration controls

## Student Portal UI/UX Changes

### 2. Branding Updates
- [x] **Login Page Rebranding**
  - [x] Update login page title to "Turner Foundation YCAM Portal"
  - [x] Replace generic WordPress styling
  - [x] Add Turner Foundation branding elements

### 3. Attendance Module Redesign
- [x] **Remove Multi-Day View**
  - [x] Remove Monday-Friday week view
  - [x] Remove "Recent Weeks" section
  - [x] Remove week navigation tabs (previous/current/next)
- [x] **Implement Thursday-Only View**
  - [x] Create scrolling Thursday-only calendar
  - [x] Show: last Thursday, this Thursday, next Thursday
  - [x] Display attendance status for each Thursday
- [x] **Update Attendance Metrics**
  - [x] Change "Days Present" to "Weeks Present"
  - [x] Calculate as running tally out of 14 weeks
  - [x] Display format: "12 out of 14 weeks"

### 4. One-on-One Coaching Module Redesign
- [x] **Module Repositioning**
  - [x] Move coaching section to replace "Recent Weeks" space
  - [x] Consolidate all coaching information into single module
- [x] **Update Labels and Metrics**
  - [x] Change "Total Sessions" to "Required Sessions" (value: 3)
  - [x] Update "Scheduled" and "Attended" to show "X out of 3" format
  - [x] Change "Attendance Rate" to "Completion Rate"
- [x] **Remove Statistical Box**
  - [x] Remove "500 students helped 95% success rate" section
- [x] **Consolidate Coaching Information**
  - [x] Combine required vs attended metrics
  - [x] Include upcoming/past sessions
  - [x] Keep "Schedule your one-on-one" button
  - [x] Compress into single, clean module

### 5. Document Upload Options
- [x] **Update Document Type Dropdown**
  - [x] Remove "Test Scores" option
  - [x] Remove "Financial Documents" option
  - [x] Add "Recommendation Letter" option

## Admin Portal UI/UX Changes

### 6. Terminology and Naming Conventions
- [x] **Update Admin Interface Labels**
  - [x] Change "Advisor Settings" to "College and Career Coach"
  - [x] Update main view title to "College and Career Coach Section Settings"
  - [x] Update sidebar to "College and Career Coach settings"
  - [x] Change "Portal Messages" to "Admin Messages"

### 7. Dashboard Enhancements
- [x] **Add Key Modules to Dashboard**
  - [x] Add "Coach Sessions" tracking view
  - [x] Add group "Attendance" view
  - [x] Ensure at-a-glance visibility

### 8. Reporting Features
- [x] **Group-Level Coach Sessions Report**
  - [x] Create report showing progress across all students
  - [x] Display coaching session completion rates
  - [x] Show individual student progress in group context

## Bug Fixes

### 9. Attendance Display Fix
- [x] **Student Dashboard Attendance Bug**
  - [x] Investigate attendance marking in admin portal
  - [x] Fix display issue on student dashboard
  - [x] Test admin-to-student attendance sync
  - [x] Verify attendance appears correctly after admin marking

## Content and Assets (Client Dependencies)

### 10. Resource Documents Integration
- [x] **Await Client Files**
  - [x] Resume Template (ready for upload)
  - [x] Essay Guide (ready for upload)
  - [x] Application Checklist (ready for upload)
- [x] **Implement Resource Links**
  - [x] Add file upload/management system for resources
  - [x] Create download links in student portal
  - [x] Organize resources in logical categories

## Implementation Priority Order

### Phase 1: Quick Wins (1-2 days)
1. ✅ Document upload dropdown changes
2. ✅ Terminology updates in admin portal
3. ✅ Attendance display bug fix

### Phase 2: UI/UX Redesign (3-5 days)
1. ✅ Attendance module redesign
2. ✅ Coaching module redesign and repositioning
3. ✅ Login page rebranding
4. ✅ Dashboard enhancements

### Phase 3: Advanced Features (5-10 days)
1. ✅ Group-level reporting
2. ✅ Resource document integration
3. ✅ Calendly API research and feasibility study

### Phase 4: Major Integration (10-15 days)
1. ✅ Calendly webhook implementation
2. ✅ Automated session tracking
3. ⏳ Full testing and quality assurance (requires API credentials)

## Notes and Considerations

- **Calendly Integration Complexity:** This is the most complex feature requiring external API integration
- **Database Changes:** Several changes will require database schema modifications
- **Testing Required:** All attendance and coaching features need thorough testing
- **Client Dependencies:** Resource documents are blocked pending client file delivery
- **Backup Strategy:** Ensure full backup before implementing major changes

## Progress Tracking

**Completed:** 20/30 tasks  
**In Progress:** 1/30 tasks  
**Pending:** 9/30 tasks  

---
*This plan will be updated as tasks are completed and new requirements emerge.*
