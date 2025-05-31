<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">高级搜索</h3>
    </div>
    <div class="card-body">
        <form action="{{ route($route, [], false) }}" method="GET" id="advanced-search-form">
            <div id="search-conditions-container">
                <!-- 搜索条件行将通过JS动态生成 -->
            </div>
            
            <div class="mb-3">
                <button type="button" class="btn btn-success btn-sm" id="add-search-condition">
                    <i class="fas fa-plus"></i> 添加搜索条件
                </button>
            </div>
            
            <div>
                <button type="submit" class="btn btn-primary">搜索</button>
                <a href="{{ route($route, [], false) }}" class="btn btn-default ml-2">重置</a>
            </div>
        </form>
    </div>
</div>

<style>
.search-condition-row {
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    border: 1px solid #dee2e6;
    margin-bottom: 10px;
}

.search-input-container .form-group {
    margin-bottom: 0;
}

.form-check-inline {
    margin-right: 0.5rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fields = @json($fields);
    
    // 从URL参数中解析搜索条件
    const urlParams = new URLSearchParams(window.location.search);
    const searchConditions = parseSearchConditionsFromUrl(urlParams);
    
    // 初始化搜索条件
    initializeSearchConditions(searchConditions);
    
    // 添加新的搜索条件
    document.getElementById('add-search-condition').addEventListener('click', function() {
        addSearchConditionRow();
    });
    
    // 删除搜索条件
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('remove-condition') || 
            event.target.parentElement.classList.contains('remove-condition')) {
            
            const searchRows = document.querySelectorAll('.search-condition-row');
            if (searchRows.length > 1) {
                const row = event.target.closest('.search-condition-row');
                row.remove();
            }
        }
    });
    
    // 监听字段选择变更
    document.addEventListener('change', function(event) {
        if (event.target.classList.contains('field-selector')) {
            updateSearchField(event.target);
        }
    });
    
    // 表单提交前处理数据 - 转换为新的简化格式（使用|分隔符）
    document.getElementById('advanced-search-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const queryParams = [];
        const searchRows = document.querySelectorAll('.search-condition-row');
        
        searchRows.forEach(function(row) {
            const fieldSelect = row.querySelector('.field-selector');
            const field = fieldSelect.value;
            
            if (!field) return;
            
            const fieldType = fields[field].htmlType;
            let queryString = '';
            
            switch (fieldType) {
                case 'text':
                    const textInput = row.querySelector('.text-search input');
                    if (textInput && textInput.value.trim()) {
                        queryString = `${field}|text|${textInput.value.trim()}`;
                    }
                    break;
                    
                case 'number':
                    const minInput = row.querySelector('.number-search input[placeholder*="最小"]');
                    const maxInput = row.querySelector('.number-search input[placeholder*="最大"]');
                    const minValue = minInput ? minInput.value.trim() : '';
                    const maxValue = maxInput ? maxInput.value.trim() : '';
                    
                    if (minValue || maxValue) {
                        queryString = `${field}|number|${minValue}|${maxValue}`;
                    }
                    break;
                    
                case 'date':
                    const startDateInput = row.querySelector('.date-search input[placeholder*="开始"]');
                    const endDateInput = row.querySelector('.date-search input[placeholder*="结束"]');
                    const startDate = startDateInput ? startDateInput.value.trim() : '';
                    const endDate = endDateInput ? endDateInput.value.trim() : '';
                    
                    if (startDate || endDate) {
                        queryString = `${field}|date|${startDate}|${endDate}`;
                    }
                    break;
                    
                case 'checkbox':
                    const checkedRadio = row.querySelector('.checkbox-search input[type="radio"]:checked');
                    if (checkedRadio && checkedRadio.value !== '') {
                        queryString = `${field}|boolean|${checkedRadio.value}`;
                    }
                    break;
            }
            
            if (queryString) {
                queryParams.push(queryString);
            }
        });
        
        // 构建新的URL
        const baseUrl = this.action;
        const url = new URL(baseUrl);
        
        // 清除旧的搜索参数
        url.search = '';
        
        // 添加新的query参数
        queryParams.forEach(function(param) {
            url.searchParams.append('query[]', param);
        });
        
        // 跳转到新URL
        window.location.href = url.toString();
    });
    
    // 从URL参数解析搜索条件 - 支持新格式（使用|分隔符）
    // 在 parseSearchConditionsFromUrl 函数中，修复日期字段的解析
    // 从URL参数解析搜索条件 - 支持新的checkbox格式
    function parseSearchConditionsFromUrl(urlParams) {
        const conditions = [];
        const queries = [];
        
        // 获取所有以 'query' 开头的参数
        for (const [key, value] of urlParams.entries()) {
            if (key === 'query[]' || key.match(/^query\[\d+\]$/)) {
                queries.push(value);
            }
        }
        
        if (queries.length > 0) {
            queries.forEach(function(queryString) {
                const parts = queryString.split('|');
                if (parts.length < 3) return;
                
                const field = parts[0];
                const type = parts[1];
                
                const condition = {
                    field: field,
                    search: '',
                    minValue: '',
                    maxValue: '',
                    startDate: '',
                    endDate: '',
                    checkboxValue: ''
                };
                
                switch (type) {
                    case 'text':
                        condition.search = parts.slice(2).join('|');
                        break;
                    case 'number':
                        condition.minValue = parts[2] || '';
                        condition.maxValue = parts[3] || '';
                        break;
                    case 'date':
                        condition.startDate = parts[2] || '';
                        condition.endDate = parts[3] || '';
                        break;
                    case 'checkbox':
                        condition.checkboxValue = parts[2] || '';
                        break;
                }
                
                conditions.push(condition);
            });
        }
        
        return conditions.length > 0 ? conditions : [{
            field: '',
            search: '',
            minValue: '',
            maxValue: '',
            startDate: '',
            endDate: '',
            checkboxValue: ''
        }];
    }
    
    // 表单提交处理 - 支持多选checkbox
    document.getElementById('advanced-search-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const queryParams = [];
        const searchRows = document.querySelectorAll('.search-condition-row');
        
        searchRows.forEach(function(row) {
            const fieldSelect = row.querySelector('.field-selector');
            const field = fieldSelect.value;
            
            if (!field) return;
            
            const fieldType = fields[field].htmlType;
            let queryString = '';
            
            switch (fieldType) {
                case 'text':
                    const textInput = row.querySelector('.text-search input');
                    if (textInput && textInput.value.trim()) {
                        queryString = `${field}|text|${textInput.value.trim()}`;
                    }
                    break;
                    
                case 'number':
                    const minInput = row.querySelector('.number-search input[placeholder*="最小"]');
                    const maxInput = row.querySelector('.number-search input[placeholder*="最大"]');
                    const minValue = minInput ? minInput.value.trim() : '';
                    const maxValue = maxInput ? maxInput.value.trim() : '';
                    
                    if (minValue || maxValue) {
                        queryString = `${field}|number|${minValue}|${maxValue}`;
                    }
                    break;
                    
                case 'date':
                    const startDateInput = row.querySelector('.date-search input[placeholder*="开始"]');
                    const endDateInput = row.querySelector('.date-search input[placeholder*="结束"]');
                    const startDate = startDateInput ? startDateInput.value.trim() : '';
                    const endDate = endDateInput ? endDateInput.value.trim() : '';
                    
                    if (startDate || endDate) {
                        queryString = `${field}|date|${startDate}|${endDate}`;
                    }
                    break;
                    
                case 'checkbox':
                    const checkboxInput = row.querySelector('.checkbox-search input[type="text"]');
                    if (checkboxInput && checkboxInput.value.trim() !== '') {
                        queryString = `${field}|checkbox|${checkboxInput.value.trim()}`;
                    }
                    break;
            }
            
            if (queryString) {
                queryParams.push(queryString);
            }
        });
        
        // 构建新的URL
        const baseUrl = this.action;
        const url = new URL(baseUrl);
        
        // 清除旧的搜索参数
        url.search = '';
        
        // 添加新的query参数
        queryParams.forEach(function(param) {
            url.searchParams.append('query[]', param);
        });
        
        // 跳转到新URL
        window.location.href = url.toString();
    });
    
    // 初始化搜索条件
    function initializeSearchConditions(conditions) {
        const container = document.getElementById('search-conditions-container');
        container.innerHTML = '';
        
        if (conditions.length === 0) {
            conditions.push({ field: '', search: '', minValue: '', maxValue: '', startDate: '', endDate: '', checkboxValue: '' });
        }
        
        conditions.forEach((condition, index) => {
            addSearchConditionRow(condition, index);
        });
    }
    
    // 添加搜索条件行
    // 在 addSearchConditionRow 函数中，确保日期字段的值正确设置
    function addSearchConditionRow(condition = null, index = null) {
        const container = document.getElementById('search-conditions-container');
        const rowIndex = index !== null ? index : container.children.length;
        
        const row = document.createElement('div');
        row.className = 'search-condition-row';
        
        let fieldsOptions = '';
        Object.keys(fields).forEach(key => {
            const field = fields[key];
            const selected = condition && condition.field === key ? 'selected' : '';
            const fieldType = field.htmlType || 'text';
            fieldsOptions += `<option value="${key}" data-type="${fieldType}" ${selected}>${field.label || key}</option>`;
        });
        
        // 修复日期字段的HTML模板
        row.innerHTML = `
            <div class="d-flex align-items-center flex-wrap">
                <div class="form-group mb-0 mr-2">
                    <select name="fields[]" class="form-control field-selector">
                        ${fieldsOptions}
                    </select>
                </div>
    
                <div class="search-input-container d-flex align-items-center">
                    <!-- Text search -->
                    <div class="form-group mb-0 mr-2 search-input text-search">
                        <input type="text" name="searches[]" class="form-control" 
                            placeholder="搜索..." 
                            value="${condition ? condition.search : ''}">
                    </div>
                    
                    <!-- Number range search -->
                    <div class="form-group mb-0 mr-2 search-input number-search d-none">
                        <div class="d-flex align-items-center">
                            <input type="number" name="min_values[]" class="form-control" 
                                placeholder="最小值" value="${condition ? condition.minValue : ''}" style="width: 100px;">
                            <span class="mx-1">至</span>
                            <input type="number" name="max_values[]" class="form-control" 
                                placeholder="最大值" value="${condition ? condition.maxValue : ''}" style="width: 100px;">
                        </div>
                    </div>
                    
                    <!-- Date range search -->
                    <div class="form-group mb-0 mr-2 search-input date-search d-none">
                        <div class="d-flex align-items-center">
                            <input type="date" name="start_dates[]" class="form-control" 
                                placeholder="开始日期" value="${condition ? condition.startDate : ''}">
                            <span class="mx-1">至</span>
                            <input type="date" name="end_dates[]" class="form-control" 
                                placeholder="结束日期" value="${condition ? condition.endDate : ''}">
                        </div>
                    </div>
                    
                    <!-- Checkbox search -->
                    <div class="form-group mb-0 mr-2 search-input checkbox-search d-none">
                        <input type="text" class="form-control" 
                            placeholder="输入值，用逗号分隔 (如: 1,2,3)" 
                            value="${condition ? condition.checkboxValue : ''}">
                    </div>
                </div>
                
                <div class="ml-2">
                    <button type="button" class="btn btn-danger btn-sm remove-condition"><i class="fas fa-times"></i></button>
                </div>
            </div>
        `;
        
        container.appendChild(row);
        
        // 更新搜索字段显示
        const fieldSelector = row.querySelector('.field-selector');
        updateSearchField(fieldSelector);
    }
    
    // 更新搜索字段类型UI
    function updateSearchField(selectElement) {
        const row = selectElement.closest('.search-condition-row');
        const fieldType = selectElement.options[selectElement.selectedIndex].getAttribute('data-type');
        
        // 隐藏所有搜索输入框
        row.querySelectorAll('.search-input').forEach(el => {
            el.classList.add('d-none');
        });
        
        // 显示对应类型的搜索框
        switch(fieldType) {
            case 'number':
                row.querySelector('.number-search').classList.remove('d-none');
                break;
            case 'date':
                row.querySelector('.date-search').classList.remove('d-none');
                break;
            case 'checkbox':
                row.querySelector('.checkbox-search').classList.remove('d-none');
                break;
            default:
                row.querySelector('.text-search').classList.remove('d-none');
                break;
        }
    }
});
</script>