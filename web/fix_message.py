import os
import glob
import re

views_dir = 'd:/Akram/property-management-system/web/src/views'
vue_files = glob.glob(f'{views_dir}/**/*.vue', recursive=True)

for file_path in vue_files:
    if 'DashboardView.vue' in file_path or 'LoginView.vue' in file_path:
        continue
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    def replace_message(match):
        raw_msg = match.group(1)
        # convert {{ var }} to ${ var }
        js_template = re.sub(r'\{\{(.*?)\}\}', r'${\1}', raw_msg)
        return f':message="`{js_template}`"'

    content = re.sub(r'message="(.*?\{\{.*?\}\}.*?)"', replace_message, content)
        
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f'Fixed {file_path}')
