
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EER Diagram: Project Management System - Database Relationships</title>
    <style>
        body {
            max-width: 880px;
            margin: 0 auto;
            padding: 32px 80px;
            position: relative;
            box-sizing: border-box;
            font-family: 'Times New Roman', serif;
            line-height: 1.6;
            color: #000;
            background: #fff;
        }
        
        h1 {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 24px;
            text-transform: uppercase;
        }
        
        h2 {
            font-size: 16px;
            font-weight: bold;
            margin-top: 24px;
            margin-bottom: 12px;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
        }
        
        h3 {
            font-size: 14px;
            font-weight: bold;
            margin-top: 16px;
            margin-bottom: 8px;
        }
        
        p {
            margin-bottom: 12px;
            text-align: justify;
        }
        
        .relationship-table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
            font-size: 12px;
        }
        
        .relationship-table th,
        .relationship-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        
        .relationship-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .entity-list {
            margin-left: 24px;
            margin-bottom: 16px;
        }
        
        .entity-list li {
            margin-bottom: 4px;
            font-size: 12px;
        }
        
        .cardinality {
            font-weight: bold;
            color: #000;
        }
        
        .table-name {
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }
        
        .legend {
            margin-top: 24px;
            padding: 16px;
            border: 1px solid #000;
            background-color: #f9f9f9;
        }
        
        .legend h3 {
            margin-top: 0;
        }
        
        .legend-item {
            margin-bottom: 8px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <h1>EER Diagram: Project Management System Database Relationships</h1>
    
    <p>This document provides a comprehensive overview of all database relationships and cardinalities within the Laravel project management system. The system is designed to support multi-tenant collaborative project management with advanced features for task tracking, team collaboration, and business operations.</p>

    <h2>I. Core Entity Relationships</h2>
    
    <h3>1.1 Primary Entity Hierarchy</h3>
    <table class="relationship-table">
        <thead>
            <tr>
                <th>Parent Entity</th>
                <th>Child Entity</th>
                <th>Cardinality</th>
                <th>Relationship Type</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="table-name">workspaces</td>
                <td class="table-name">teams</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each workspace can have multiple teams</td>
            </tr>
            <tr>
                <td class="table-name">workspaces</td>
                <td class="table-name">projects</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each workspace can have multiple projects</td>
            </tr>
            <tr>
                <td class="table-name">teams</td>
                <td class="table-name">projects</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key (Optional)</td>
                <td>Each team can have multiple projects (optional relationship)</td>
            </tr>
            <tr>
                <td class="table-name">projects</td>
                <td class="table-name">tasks</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each project can have multiple tasks</td>
            </tr>
            <tr>
                <td class="table-name">tasks</td>
                <td class="table-name">tasks</td>
                <td class="cardinality">1:M</td>
                <td>Self-Referencing</td>
                <td>Parent-child relationship for subtasks</td>
            </tr>
            <tr>
                <td class="table-name">statuses</td>
                <td class="table-name">tasks</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each status can be assigned to multiple tasks</td>
            </tr>
        </tbody>
    </table>

    <h3>1.2 User Assignment Relationships</h3>
    <table class="relationship-table">
        <thead>
            <tr>
                <th>User Role</th>
                <th>Target Entity</th>
                <th>Cardinality</th>
                <th>Relationship Type</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="table-name">users (owner)</td>
                <td class="table-name">projects</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each user can own multiple projects</td>
            </tr>
            <tr>
                <td class="table-name">users (assignee)</td>
                <td class="table-name">tasks</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key (Optional)</td>
                <td>Each user can be assigned to multiple tasks</td>
            </tr>
            <tr>
                <td class="table-name">users (reporter)</td>
                <td class="table-name">tasks</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each user can report multiple tasks</td>
            </tr>
            <tr>
                <td class="table-name">users (owner)</td>
                <td class="table-name">goals</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each user can own multiple goals</td>
            </tr>
        </tbody>
    </table>

    <h2>II. Junction Table Relationships (Many-to-Many)</h2>
    
    <h3>2.1 User-Entity Associations</h3>
    <table class="relationship-table">
        <thead>
            <tr>
                <th>Junction Table</th>
                <th>Entity A</th>
                <th>Entity B</th>
                <th>Cardinality</th>
                <th>Additional Fields</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="table-name">workspace_user</td>
                <td class="table-name">workspaces</td>
                <td class="table-name">users</td>
                <td class="cardinality">M:M</td>
                <td>role, joined_at</td>
            </tr>
            <tr>
                <td class="table-name">team_user</td>
                <td class="table-name">teams</td>
                <td class="table-name">users</td>
                <td class="cardinality">M:M</td>
                <td>role, joined_at</td>
            </tr>
            <tr>
                <td class="table-name">project_user</td>
                <td class="table-name">projects</td>
                <td class="table-name">users</td>
                <td class="cardinality">M:M</td>
                <td>role</td>
            </tr>
            <tr>
                <td class="table-name">conversation_user</td>
                <td class="table-name">conversations</td>
                <td class="table-name">users</td>
                <td class="cardinality">M:M</td>
                <td>role, joined_at, last_read_at</td>
            </tr>
            <tr>
                <td class="table-name">event_participants</td>
                <td class="table-name">events</td>
                <td class="table-name">users</td>
                <td class="cardinality">M:M</td>
                <td>status</td>
            </tr>
        </tbody>
    </table>

    <h3>2.2 Task-Related Associations</h3>
    <table class="relationship-table">
        <thead>
            <tr>
                <th>Junction Table</th>
                <th>Entity A</th>
                <th>Entity B</th>
                <th>Cardinality</th>
                <th>Additional Fields</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="table-name">task_tag</td>
                <td class="table-name">tasks</td>
                <td class="table-name">tags</td>
                <td class="cardinality">M:M</td>
                <td>None</td>
            </tr>
            <tr>
                <td class="table-name">task_milestone</td>
                <td class="table-name">tasks</td>
                <td class="table-name">milestones</td>
                <td class="cardinality">M:M</td>
                <td>None</td>
            </tr>
            <tr>
                <td class="table-name">task_pipeline</td>
                <td class="table-name">tasks</td>
                <td class="table-name">pipelines</td>
                <td class="cardinality">M:M</td>
                <td>status_id, order</td>
            </tr>
            <tr>
                <td class="table-name">task_dependencies</td>
                <td class="table-name">tasks</td>
                <td class="table-name">tasks</td>
                <td class="cardinality">M:M</td>
                <td>type (blocks, relates_to, duplicates)</td>
            </tr>
            <tr>
                <td class="table-name">goal_tasks</td>
                <td class="table-name">goals</td>
                <td class="table-name">tasks</td>
                <td class="cardinality">M:M</td>
                <td>None</td>
            </tr>
            <tr>
                <td class="table-name">pipeline_status</td>
                <td class="table-name">pipelines</td>
                <td class="table-name">statuses</td>
                <td class="cardinality">M:M</td>
                <td>order</td>
            </tr>
        </tbody>
    </table>

    <h2>III. Polymorphic Relationships</h2>
    
    <h3>3.1 Morphable Entity Relationships</h3>
    <table class="relationship-table">
        <thead>
            <tr>
                <th>Polymorphic Table</th>
                <th>Morph Fields</th>
                <th>Target Entities</th>
                <th>Cardinality</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="table-name">comments</td>
                <td>commentable_type, commentable_id</td>
                <td>tasks, projects, goals</td>
                <td class="cardinality">M:1</td>
                <td>Comments can be attached to multiple entity types</td>
            </tr>
            <tr>
                <td class="table-name">attachments</td>
                <td>attachable_type, attachable_id</td>
                <td>tasks, projects, comments, messages</td>
                <td class="cardinality">M:1</td>
                <td>Files can be attached to multiple entity types</td>
            </tr>
            <tr>
                <td class="table-name">mentions</td>
                <td>mentionable_type, mentionable_id</td>
                <td>tasks, comments, messages</td>
                <td class="cardinality">M:1</td>
                <td>Users can be mentioned in multiple entity types</td>
            </tr>
            <tr>
                <td class="table-name">reactions</td>
                <td>reactable_type, reactable_id</td>
                <td>tasks, comments, messages</td>
                <td class="cardinality">M:1</td>
                <td>Reactions can be added to multiple entity types</td>
            </tr>
            <tr>
                <td class="table-name">notifications</td>
                <td>notifiable_type, notifiable_id</td>
                <td>tasks, projects, comments, messages</td>
                <td class="cardinality">M:1</td>
                <td>Notifications can reference multiple entity types</td>
            </tr>
            <tr>
                <td class="table-name">activity_logs</td>
                <td>subject_type, subject_id</td>
                <td>tasks, projects, users, teams</td>
                <td class="cardinality">M:1</td>
                <td>Activities can be logged for multiple entity types</td>
            </tr>
            <tr>
                <td class="table-name">audit_logs</td>
                <td>auditable_type, auditable_id</td>
                <td>tasks, projects, users, teams</td>
                <td class="cardinality">M:1</td>
                <td>Audit trail for multiple entity types</td>
            </tr>
            <tr>
                <td class="table-name">reminders</td>
                <td>remindable_type, remindable_id</td>
                <td>tasks, projects, events</td>
                <td class="cardinality">M:1</td>
                <td>Reminders can be set for multiple entity types</td>
            </tr>
            <tr>
                <td class="table-name">custom_field_values</td>
                <td>entity_type, entity_id</td>
                <td>tasks, projects, users</td>
                <td class="cardinality">M:1</td>
                <td>Custom field values for multiple entity types</td>
            </tr>
        </tbody>
    </table>

    <h2>IV. Support Entity Relationships</h2>
    
    <h3>4.1 Task Management Support</h3>
    <table class="relationship-table">
        <thead>
            <tr>
                <th>Parent Entity</th>
                <th>Child Entity</th>
                <th>Cardinality</th>
                <th>Relationship Type</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="table-name">tasks</td>
                <td class="table-name">subtasks</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each task can have multiple subtasks</td>
            </tr>
            <tr>
                <td class="table-name">tasks</td>
                <td class="table-name">checklists</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each task can have multiple checklists</td>
            </tr>
            <tr>
                <td class="table-name">checklists</td>
                <td class="table-name">checklist_items</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each checklist can have multiple items</td>
            </tr>
            <tr>
                <td class="table-name">tasks</td>
                <td class="table-name">time_logs</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each task can have multiple time log entries</td>
            </tr>
            <tr>
                <td class="table-name">tasks</td>
                <td class="table-name">recurring_tasks</td>
                <td class="cardinality">1:1</td>
                <td>Direct Foreign Key</td>
                <td>Each task can have one recurring configuration</td>
            </tr>
            <tr>
                <td class="table-name">projects</td>
                <td class="table-name">milestones</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each project can have multiple milestones</td>
            </tr>
        </tbody>
    </table>

    <h3>4.2 Workspace Support</h3>
    <table class="relationship-table">
        <thead>
            <tr>
                <th>Parent Entity</th>
                <th>Child Entity</th>
                <th>Cardinality</th>
                <th>Relationship Type</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="table-name">workspaces</td>
                <td class="table-name">statuses</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each workspace can define multiple statuses</td>
            </tr>
            <tr>
                <td class="table-name">workspaces</td>
                <td class="table-name">tags</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each workspace can define multiple tags</td>
            </tr>
            <tr>
                <td class="table-name">workspaces</td>
                <td class="table-name">pipelines</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each workspace can define multiple pipelines</td>
            </tr>
            <tr>
                <td class="table-name">workspaces</td>
                <td class="table-name">goals</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each workspace can have multiple goals</td>
            </tr>
            <tr>
                <td class="table-name">workspaces</td>
                <td class="table-name">custom_fields</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each workspace can define multiple custom fields</td>
            </tr>
        </tbody>
    </table>

    <h2>V. Communication & Collaboration</h2>
    
    <h3>5.1 Communication Relationships</h3>
    <table class="relationship-table">
        <thead>
            <tr>
                <th>Parent Entity</th>
                <th>Child Entity</th>
                <th>Cardinality</th>
                <th>Relationship Type</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="table-name">workspaces</td>
                <td class="table-name">conversations</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each workspace can have multiple conversations</td>
            </tr>
            <tr>
                <td class="table-name">conversations</td>
                <td class="table-name">messages</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each conversation can have multiple messages</td>
            </tr>
            <tr>
                <td class="table-name">users</td>
                <td class="table-name">messages</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each user can send multiple messages</td>
            </tr>
            <tr>
                <td class="table-name">comments</td>
                <td class="table-name">comments</td>
                <td class="cardinality">1:M</td>
                <td>Self-Referencing</td>
                <td>Comments can have replies (nested comments)</td>
            </tr>
            <tr>
                <td class="table-name">users</td>
                <td class="table-name">mentions</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each user can receive multiple mentions</td>
            </tr>
            <tr>
                <td class="table-name">users</td>
                <td class="table-name">mentions</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key (mentioned_by)</td>
                <td>Each user can create multiple mentions</td>
            </tr>
        </tbody>
    </table>

    <h2>VI. Knowledge Management & Forms</h2>
    
    <h3>6.1 Wiki & Knowledge Base</h3>
    <table class="relationship-table">
        <thead>
            <tr>
                <th>Parent Entity</th>
                <th>Child Entity</th>
                <th>Cardinality</th>
                <th>Relationship Type</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="table-name">workspaces</td>
                <td class="table-name">wikis</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each workspace can have multiple wiki pages</td>
            </tr>
            <tr>
                <td class="table-name">wikis</td>
                <td class="table-name">wikis</td>
                <td class="cardinality">1:M</td>
                <td>Self-Referencing</td>
                <td>Wiki pages can have child pages</td>
            </tr>
            <tr>
                <td class="table-name">wikis</td>
                <td class="table-name">wiki_revisions</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each wiki page can have multiple revisions</td>
            </tr>
            <tr>
                <td class="table-name">users</td>
                <td class="table-name">wiki_revisions</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each user can create multiple wiki revisions</td>
            </tr>
        </tbody>
    </table>

    <h3>6.2 Forms & Custom Fields</h3>
    <table class="relationship-table">
        <thead>
            <tr>
                <th>Parent Entity</th>
                <th>Child Entity</th>
                <th>Cardinality</th>
                <th>Relationship Type</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="table-name">workspaces</td>
                <td class="table-name">forms</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each workspace can have multiple forms</td>
            </tr>
            <tr>
                <td class="table-name">forms</td>
                <td class="table-name">form_responses</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each form can have multiple responses</td>
            </tr>
            <tr>
                <td class="table-name">custom_fields</td>
                <td class="table-name">custom_field_values</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each custom field can have multiple values</td>
            </tr>
        </tbody>
    </table>

    <h2>VII. SaaS & Business Operations</h2>
    
    <h3>7.1 Subscription Management</h3>
    <table class="relationship-table">
        <thead>
            <tr>
                <th>Parent Entity</th>
                <th>Child Entity</th>
                <th>Cardinality</th>
                <th>Relationship Type</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="table-name">plans</td>
                <td class="table-name">subscriptions</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each plan can have multiple subscriptions</td>
            </tr>
            <tr>
                <td class="table-name">workspaces</td>
                <td class="table-name">subscriptions</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each workspace can have multiple subscriptions</td>
            </tr>
            <tr>
                <td class="table-name">workspaces</td>
                <td class="table-name">invoices</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each workspace can have multiple invoices</td>
            </tr>
            <tr>
                <td class="table-name">workspaces</td>
                <td class="table-name">invitations</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each workspace can send multiple invitations</td>
            </tr>
            <tr>
                <td class="table-name">users</td>
                <td class="table-name">invitations</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key (invited_by)</td>
                <td>Each user can send multiple invitations</td>
            </tr>
        </tbody>
    </table>

    <h2>VIII. System Integration & Tracking</h2>
    
    <h3>8.1 API & Integration</h3>
    <table class="relationship-table">
        <thead>
            <tr>
                <th>Parent Entity</th>
                <th>Child Entity</th>
                <th>Cardinality</th>
                <th>Relationship Type</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="table-name">users</td>
                <td class="table-name">api_tokens</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each user can have multiple API tokens</td>
            </tr>
            <tr>
                <td class="table-name">workspaces</td>
                <td class="table-name">integrations</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each workspace can have multiple integrations</td>
            </tr>
            <tr>
                <td class="table-name">workspaces</td>
                <td class="table-name">webhooks</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each workspace can have multiple webhooks</td>
            </tr>
            <tr>
                <td class="table-name">webhooks</td>
                <td class="table-name">webhook_deliveries</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each webhook can have multiple delivery attempts</td>
            </tr>
            <tr>
                <td class="table-name">workspaces</td>
                <td class="table-name">reports</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each workspace can have multiple reports</td>
            </tr>
        </tbody>
    </table>

    <h3>8.2 Scheduling & Events</h3>
    <table class="relationship-table">
        <thead>
            <tr>
                <th>Parent Entity</th>
                <th>Child Entity</th>
                <th>Cardinality</th>
                <th>Relationship Type</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="table-name">workspaces</td>
                <td class="table-name">events</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each workspace can have multiple events</td>
            </tr>
            <tr>
                <td class="table-name">users</td>
                <td class="table-name">events</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key (created_by)</td>
                <td>Each user can create multiple events</td>
            </tr>
            <tr>
                <td class="table-name">users</td>
                <td class="table-name">reminders</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each user can have multiple reminders</td>
            </tr>
            <tr>
                <td class="table-name">users</td>
                <td class="table-name">notifications</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each user can receive multiple notifications</td>
            </tr>
        </tbody>
    </table>

    <h2>IX. Data Management & Storage</h2>
    
    <h3>9.1 File & Media Management</h3>
    <table class="relationship-table">
        <thead>
            <tr>
                <th>Parent Entity</th>
                <th>Child Entity</th>
                <th>Cardinality</th>
                <th>Relationship Type</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="table-name">users</td>
                <td class="table-name">attachments</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each user can upload multiple attachments</td>
            </tr>
            <tr>
                <td class="table-name">workspaces</td>
                <td class="table-name">media</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each workspace can have multiple media files</td>
            </tr>
            <tr>
                <td class="table-name">users</td>
                <td class="table-name">media</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each user can upload multiple media files</td>
            </tr>
            <tr>
                <td class="table-name">workspaces</td>
                <td class="table-name">backups</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each workspace can have multiple backups</td>
            </tr>
        </tbody>
    </table>

    <h3>9.2 Import/Export Operations</h3>
    <table class="relationship-table">
        <thead>
            <tr>
                <th>Parent Entity</th>
                <th>Child Entity</th>
                <th>Cardinality</th>
                <th>Relationship Type</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="table-name">workspaces</td>
                <td class="table-name">imports</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each workspace can have multiple import operations</td>
            </tr>
            <tr>
                <td class="table-name">users</td>
                <td class="table-name">imports</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each user can initiate multiple imports</td>
            </tr>
            <tr>
                <td class="table-name">workspaces</td>
                <td class="table-name">exports</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each workspace can have multiple export operations</td>
            </tr>
            <tr>
                <td class="table-name">users</td>
                <td class="table-name">exports</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each user can initiate multiple exports</td>
            </tr>
        </tbody>
    </table>

    <h2>X. User Preferences & Settings</h2>
    
    <h3>10.1 Configuration Management</h3>
    <table class="relationship-table">
        <thead>
            <tr>
                <th>Parent Entity</th>
                <th>Child Entity</th>
                <th>Cardinality</th>
                <th>Relationship Type</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="table-name">users</td>
                <td class="table-name">user_preferences</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each user can have multiple preference settings</td>
            </tr>
            <tr>
                <td class="table-name">workspaces</td>
                <td class="table-name">settings</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each workspace can have multiple settings</td>
            </tr>
            <tr>
                <td class="table-name">workspaces</td>
                <td class="table-name">email_templates</td>
                <td class="cardinality">1:M</td>
                <td>Direct Foreign Key</td>
                <td>Each workspace can have multiple email templates</td>
            </tr>
        </tbody>
    </table>

    <div class="legend">
        <h3>Legend & Cardinality Notation</h3>
        <div class="legend-item"><strong>1:1</strong> - One-to-One: Each record in the parent table relates to exactly one record in the child table</div>
        <div class="legend-item"><strong>1:M</strong> - One-to-Many: Each record in the parent table can relate to multiple records in the child table</div>
        <div class="legend-item"><strong>M:M</strong> - Many-to-Many: Records in both tables can relate to multiple records in the other table (requires junction table)</div>
        <div class="legend-item"><strong>Polymorphic</strong> - A relationship where one table can be related to multiple other tables through type and ID fields</div>
        <div class="legend-item"><strong>Self-Referencing</strong> - A table that references itself, creating parent-child relationships within the same entity</div>
        <div class="legend-item"><strong>Optional (Nullable)</strong> - Foreign key relationships that can be null, indicating optional relationships</div>
    </div>

    <p style="margin-top: 32px; font-size: 11px; text-align: center; color: #666;">
        This EER diagram documentation represents the complete database schema for the Laravel project management system, designed to support multi-tenant collaborative workflows with comprehensive tracking, communication, and business management capabilities.
    </p>
</body>
</html>
