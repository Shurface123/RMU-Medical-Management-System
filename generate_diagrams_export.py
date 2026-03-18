import os
import re

html_template = """
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RMU Medical Sickbay - System Architecture Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --bg-body: #f1f5f9;
            --bg-card: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --sidebar-width: 280px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background: var(--bg-body); 
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: #0f172a;
            color: #f8fafc;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            padding: 2rem 1.5rem;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
            z-index: 10;
        }

        .sidebar-header {
            margin-bottom: 2.5rem;
            border-bottom: 1px solid #334155;
            padding-bottom: 1.5rem;
        }

        .sidebar-header h2 {
            font-size: 1.25rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(120deg, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-group {
            margin-bottom: 2rem;
        }

        .nav-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #cbd5e1;
            text-decoration: none;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            transition: all 0.2s ease;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 2.5rem 4rem;
            max-width: 1400px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 3rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 1.5rem;
        }

        .page-title h1 {
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }

        .page-title p {
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        .btn-export {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
        }

        .btn-export:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        /* Diagram Cards */
        .diagram-section {
            position: absolute;
            visibility: hidden;
            opacity: 0;
            top: 0;
            left: 0;
            height: 0;
            overflow: hidden;
        }

        .diagram-section.active {
            position: relative;
            visibility: visible;
            opacity: 1;
            top: auto;
            left: auto;
            height: auto;
            overflow: visible;
            animation: fadeIn 0.4s ease-out forwards;
        }

        .diagram-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2.5rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            border: 1px solid rgba(0,0,0,0.02);
            overflow-x: auto;
            page-break-inside: avoid;
        }

        .diagram-card .mermaid {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Print Styles */
        @media print {
            body { background: #fff; display: block; }
            .sidebar, .btn-export { display: none !important; }
            .main-content { margin-left: 0; padding: 0; max-width: none; }
            .diagram-section { display: block !important; position: relative !important; visibility: visible !important; opacity: 1 !important; height: auto !important; }
            .diagram-card { box-shadow: none; border: 1px solid #ccc; margin-bottom: 2rem; page-break-inside: avoid; }
            .page-header { margin-bottom: 2rem; border-bottom: 2px solid #000; }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2><i class="fa-solid fa-hospital"></i> RMU Sickbay</h2>
        </div>
        
        <div class="nav-group">
            <h3 class="nav-title">Architecture Views</h3>
            <a href="#" class="nav-link active" onclick="showTab('all', this)"><i class="fa-solid fa-layer-group"></i> All Diagrams</a>
            <a href="#" class="nav-link" onclick="showTab('usecases', this)"><i class="fa-solid fa-users"></i> Use Cases</a>
            <a href="#" class="nav-link" onclick="showTab('flowcharts', this)"><i class="fa-solid fa-code-branch"></i> Flowcharts</a>
            <a href="#" class="nav-link" onclick="showTab('sequences', this)"><i class="fa-solid fa-clock-rotate-left"></i> Sequences</a>
            <a href="#" class="nav-link" onclick="showTab('er', this)"><i class="fa-solid fa-database"></i> Entity Relationship</a>
        </div>
        
        <div class="nav-group">
            <h3 class="nav-title">Filter by Role</h3>
            <a href="#" class="nav-link" onclick="filterRole('Admin', this)"><i class="fa-solid fa-user-tie"></i> Admin / Management</a>
            <a href="#" class="nav-link" onclick="filterRole('Doctor', this)"><i class="fa-solid fa-user-md"></i> Doctors</a>
            <a href="#" class="nav-link" onclick="filterRole('Nurse', this)"><i class="fa-solid fa-user-nurse"></i> Nurses</a>
            <a href="#" class="nav-link" onclick="filterRole('Pharmacist', this)"><i class="fa-solid fa-capsules"></i> Pharmacists</a>
            <a href="#" class="nav-link" onclick="filterRole('Lab Tech', this)"><i class="fa-solid fa-microscope"></i> Lab Technicians</a>
            <a href="#" class="nav-link" onclick="filterRole('Support', this)"><i class="fa-solid fa-broom"></i> Support Staff</a>
            <a href="#" class="nav-link" onclick="filterRole('Patient', this)"><i class="fa-solid fa-user"></i> Patients</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1>System Architecture Dashboard</h1>
                <p>Interactive overview of workflows, roles, and integrations.</p>
            </div>
            <button class="btn-export" onclick="window.print()">
                <i class="fa-solid fa-print"></i> Export to PDF
            </button>
        </div>

        <div id="diagrams-container">
            <!-- DIAGRAMS RENDERED HERE -->
        </div>
    </main>

    <!-- Scripts -->
    <script type="module">
        import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs';
        
        // Remove FontAwesome parsing issues natively
        mermaid.initialize({ 
            startOnLoad: true, 
            theme: 'default', 
            securityLevel: 'loose',
            fontFamily: 'Inter',
            flowchart: { htmlLabels: false, curve: 'linear' },
            sequence: { useMaxWidth: false, showSequenceNumbers: true },
            themeVariables: {
                primaryColor: '#f1f5f9',
                primaryTextColor: '#1e293b',
                primaryBorderColor: '#cbd5e1',
                lineColor: '#64748b',
                secondaryColor: '#f8fafc',
                tertiaryColor: '#fff',
                fontFamily: 'Inter, sans-serif'
            }
        });

        // Tab and Filter Logic
        window.showTab = function(type, el) {
            document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
            el.classList.add('active');
            
            const sections = document.querySelectorAll('.diagram-section');
            sections.forEach(sec => {
                if(type === 'all' || sec.dataset.type === type) {
                    sec.classList.add('active');
                } else {
                    sec.classList.remove('active');
                }
            });
        };
        
        window.filterRole = function(role, el) {
            document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
            el.classList.add('active');
            
            const sections = document.querySelectorAll('.diagram-section');
            sections.forEach(sec => {
                const roles = sec.dataset.roles || '';
                if(roles.toLowerCase().includes(role.toLowerCase())) {
                    sec.classList.add('active');
                } else {
                    sec.classList.remove('active');
                }
            });
        };
    </script>
</body>
</html>
"""

# Categorize files by type for metadata tagging
files_map = {
    'usecases': 'diagrams_use_case.md',
    'flowcharts': 'diagrams_flowchart.md',
    'sequences': 'diagrams_sequence.md',
    'er': 'diagrams_er.md'
}

diagrams_html_parts = []

for diag_type, filename in files_map.items():
    if os.path.exists(filename):
        content = open(filename, 'r', encoding='utf-8').read()
        blocks = re.findall(r'```mermaid(.*?)```', content, re.DOTALL)
        
        for block in blocks:
            # Extract roles from title if present for filtering
            roles_match = re.search(r'Roles:\s*(.*?)\s*\|', block)
            roles_str = roles_match.group(1) if roles_match else "All"
            
            # Since HTML escaping broke Mermaid v10.9 parsing with <> tags, we use the original clean block.
            safe_block = block.strip()
            
            diagrams_html_parts.append(f'''
            <div class="diagram-section active" data-type="{diag_type}" data-roles="{roles_str}">
                <div class="diagram-card">
                    <pre class="mermaid">
{safe_block}
                    </pre>
                </div>
            </div>
            ''')

diagrams_html = "".join(diagrams_html_parts)
html_out = html_template.replace('<!-- DIAGRAMS RENDERED HERE -->', diagrams_html)

with open('diagrams_master_export.html', 'w', encoding='utf-8') as out:
    out.write(html_out)

print("Enhanced diagrams_master_export.html generated successfully.")
