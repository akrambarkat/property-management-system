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

    # 1. Remove error-banner and toast-banner blocks
    content = re.sub(r'<div v-if="errorMsg" class="error-banner">.*?</div>\n*', '', content, flags=re.DOTALL)
    content = re.sub(r'<transition name="fade">\s*<div v-if="toastMsg" class="toast-banner">.*?</div>\s*</transition>\n*', '', content, flags=re.DOTALL)

    # 2. Add imports for Toast and ConfirmModal
    if 'useToastStore' not in content:
        content = content.replace("import FormField from '@/components/common/FormField.vue'", 
                                  "import FormField from '@/components/common/FormField.vue'\nimport ConfirmModal from '@/components/common/ConfirmModal.vue'\nimport { useToastStore } from '@/stores/toast'")
    
    if 'useToastStore' not in content and 'FormField.vue' not in content:
         content = content.replace("import EnterpriseTable from '@/components/common/EnterpriseTable.vue'",
                                  "import EnterpriseTable from '@/components/common/EnterpriseTable.vue'\nimport ConfirmModal from '@/components/common/ConfirmModal.vue'\nimport { useToastStore } from '@/stores/toast'")

    # 3. Add toast instance
    if 'const toast = useToastStore()' not in content:
        content = content.replace('const loading = ref(false)', 'const loading = ref(false)\nconst toast = useToastStore()')

    # 4. Remove toastMsg and errorMsg refs
    content = re.sub(r'const errorMsg = ref\(\'\'\)\n?', '', content)
    content = re.sub(r'const toastMsg = ref\(\'\'\)\n?', '', content)

    # 5. Replace showToast implementation with nothing
    content = re.sub(r'function showToast\(msg\) \{.*?\setTimeout.*?\}\n?', '', content, flags=re.DOTALL)

    # 6. Replace usages
    content = re.sub(r"errorMsg\.value = (err\.response\?\.data\?\.message \|\| .*?)\s*\n", r"toast.error(\1)\n", content)
    content = re.sub(r"errorMsg\.value = (.*?)\s*\n", r"toast.error(\1)\n", content)
    
    content = re.sub(r'showToast\((.*?)\)', r'toast.success(\1)', content)

    # 7. Add Delete Modal Template replacement
    delete_modal_pattern = r'<Dialog\s+v-model:visible="showDeleteModal"[^>]*>.*?<p class="delete-msg">(.*?)</p>.*?</Dialog>'
    match = re.search(delete_modal_pattern, content, flags=re.DOTALL)
    if match:
        msg_html = match.group(1).replace('"', '&quot;')
        new_modal = f'<ConfirmModal\n      v-model:visible="showDeleteModal"\n      title="تأكيد الحذف"\n      message="{msg_html}"\n      variant="danger"\n      confirmText="تأكيد الحذف"\n      @confirm="deleteItemConfirmed"\n    />'
        content = re.sub(delete_modal_pattern, new_modal, content, flags=re.DOTALL)
        
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f'Processed {file_path}')
