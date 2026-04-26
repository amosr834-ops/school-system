# Collaborative Task Management Web App
# Software Requirements Specification (SRS)
**Version:** 1.0  
**Date:** April 26, 2026  
**Prepared for:** Semester-Long Project

## 1. Introduction

### 1.1 Purpose
This document defines the software requirements for a Collaborative Task Management Web Application. The system enables users to register/login, collaborate in teams, create and assign tasks, set deadlines and priorities, comment on tasks, receive notifications, and deploy the solution with CI/CD support.

### 1.2 Scope
The application provides a web-based platform for collaborative project work in academic or team environments. Core scope includes:
- User authentication using JWT
- Team creation and member management
- Task lifecycle management
- Task-based chat/comments
- Notification delivery for team/task events
- Deployment readiness through Docker and CI/CD workflow

### 1.3 Definitions, Acronyms, and Abbreviations
- **SRS:** Software Requirements Specification
- **JWT:** JSON Web Token
- **CI/CD:** Continuous Integration / Continuous Deployment
- **API:** Application Programming Interface
- **UI:** User Interface

### 1.4 References
- Project repository: `school-system`
- CI workflow: `.github/workflows/ci.yml`
- Database schema script: `Database/elimumanagementsystem.sql`

## 2. Overall Description

### 2.1 Product Perspective
The system is a full-stack web application:
- Frontend: React + Vite
- Backend: PHP APIs
- Database: MySQL
- Deployment: Docker Compose
- Build/Validation pipeline: GitHub Actions

### 2.2 Product Functions
The system shall:
- Register new users and authenticate existing users
- Issue JWT tokens and secure protected endpoints
- Allow users to create teams and add members by email
- Allow users to create, assign, update, and track tasks
- Support task metadata: status, priority, and due date
- Enable comments on each task for team collaboration
- Generate and display notifications

### 2.3 User Classes and Characteristics
- **Team Owner:** Creates teams, adds members, creates and assigns tasks
- **Team Member:** Views team tasks, updates assigned tasks, comments
- **Authenticated User:** Can view notifications and personal dashboard data

### 2.4 Operating Environment
- Browser: Chrome, Edge, Firefox (modern versions)
- Backend runtime: PHP 8.x
- Database: MySQL 8.x
- OS: Windows/Linux/macOS (development and deployment)
- Optional runtime setup: XAMPP or Docker

### 2.5 Design and Implementation Constraints
- Authentication mechanism: JWT
- Relational database: MySQL
- REST-like endpoint model in PHP
- Single-page frontend architecture with React

### 2.6 Assumptions and Dependencies
- Users access through stable internet/local network
- MySQL service is available before backend operations
- Environment variables are correctly configured for secrets and DB connection

## 3. External Interface Requirements

### 3.1 User Interface Requirements
- The UI shall provide:
  - Auth screens (Register/Login)
  - Team management panel
  - Task creation and task list with editable fields
  - Task comment/chat section
  - Notifications panel
- The UI shall be responsive for desktop and mobile viewports.

### 3.2 Software Interfaces
- Frontend communicates with backend via HTTP/JSON endpoints:
  - `POST /api/register.php`
  - `POST /api/Login.php`
  - `GET /api/me.php`
  - `GET|POST /api/teams.php`
  - `GET|POST|PUT /api/tasks.php`
  - `GET|POST /api/comments.php`
  - `GET|POST /api/notifications.php`

### 3.3 Database Interfaces
The system shall persist and query data in MySQL tables:
- `users`
- `teams`
- `team_members`
- `tasks`
- `task_comments`
- `notifications`

### 3.4 Communication Interfaces
- HTTP/HTTPS for client-server communication
- JSON request/response payloads
- Bearer token headers for authenticated routes

## 4. Functional Requirements

### 4.1 Authentication
- **FR-001:** The system shall allow a new user to register with name, email, and password.
- **FR-002:** The system shall allow existing users to log in with email and password.
- **FR-003:** The system shall issue a JWT upon successful login/registration.
- **FR-004:** The system shall reject unauthorized access to protected endpoints.

### 4.2 Team Management
- **FR-005:** The system shall allow authenticated users to create teams.
- **FR-006:** The system shall add the creator as team owner.
- **FR-007:** The system shall allow team owners to add members by email.
- **FR-008:** The system shall list teams a user belongs to.

### 4.3 Task Management
- **FR-009:** The system shall allow users to create tasks within a team.
- **FR-010:** The system shall support task assignment to a user (optional assignee).
- **FR-011:** The system shall store task title, description, priority, status, and due date.
- **FR-012:** The system shall allow authorized users to update task status, priority, and due date.
- **FR-013:** The system shall list tasks relevant to a user (created, assigned, or team-related).

### 4.4 Comment/Chat
- **FR-014:** The system shall allow authorized users to post comments on a task.
- **FR-015:** The system shall display task comments in chronological order.
- **FR-016:** The system shall restrict task comments to users with task access.

### 4.5 Notifications
- **FR-017:** The system shall create notifications when:
  - a user is added to a team
  - a task is assigned
  - a task receives a new comment (for relevant participants)
- **FR-018:** The system shall allow users to view notifications.
- **FR-019:** The system shall allow users to mark notifications as read.

### 4.6 Deployment and Pipeline
- **FR-020:** The system shall be deployable using Docker Compose.
- **FR-021:** The system shall validate builds through CI workflow on push and pull request.
- **FR-022:** The system shall support optional image publishing when Docker Hub secrets are configured.

## 5. Non-Functional Requirements

### 5.1 Security
- Passwords shall be stored as hashes (bcrypt).
- JWT secret shall be externally configurable via environment variables.
- Protected APIs shall require valid bearer tokens.

### 5.2 Performance
- Common dashboard operations (load profile, teams, tasks, notifications) should complete within acceptable interactive latency under normal load.

### 5.3 Reliability
- The system shall preserve data integrity using relational constraints and foreign keys.
- API operations shall return structured error responses for failed requests.

### 5.4 Usability
- Core workflows (register, login, team creation, task creation, commenting) shall be discoverable from the primary dashboard.
- Form validation messages shall guide users on missing/invalid inputs.

### 5.5 Maintainability
- Frontend and backend concerns shall remain separated.
- Configuration shall be environment-driven (development vs Docker deployment).

### 5.6 Portability
- Application shall run in local environments (XAMPP-style stack) and containerized environments (Docker).

## 6. Use Case Summary

### UC-01: Register and Login
1. User opens application.
2. User registers account or logs in.
3. System returns JWT and user profile.

### UC-02: Create Team and Add Member
1. Owner creates a team.
2. Owner adds member using email.
3. System creates membership and notification for added member.

### UC-03: Create and Assign Task
1. User selects a team and enters task details.
2. User sets priority and due date.
3. User assigns task to member email.
4. System stores task and issues assignment notification.

### UC-04: Collaborate on Task
1. User opens task chat/comments.
2. User posts comment.
3. System stores comment and notifies relevant participants.

### UC-05: Track Progress
1. User updates task status (Todo/In Progress/Done).
2. User adjusts priority or deadline when needed.
3. System saves updates and reflects them in task list.

## 7. Acceptance Criteria

- Users can register and login successfully using JWT flow.
- Team owner can create a team and add at least one member.
- User can create task with due date and priority.
- Assigned member can see assigned task and related notification.
- Users with access can exchange comments on selected task.
- CI workflow runs frontend build successfully on GitHub actions triggers.
- Docker deployment brings up frontend, backend, and database services.

## 8. Future Enhancements (Optional)

- Google OAuth login integration
- Real-time notifications via WebSocket
- Role-based access controls beyond owner/member
- Audit logs and reporting dashboards



