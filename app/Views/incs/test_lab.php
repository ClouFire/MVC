<?php
$createdCredentials = session()->getFlash('created_credentials');
$testUserErrors = session()->get('test_user_errors', []);
$testUserData = session()->get('test_user_data', []);
$phpExecution = session()->getFlash('php_execution');
$sqlConsole = session()->getFlash('sql_console');
?>

<style>
    .test-lab-toggle {
        position: fixed;
        top: 50%;
        right: 0;
        z-index: 1050;
        display: inline-flex;
        width: 2.75rem;
        height: 3rem;
        align-items: center;
        justify-content: center;
        padding: 0;
        overflow: hidden;
        white-space: nowrap;
        border: 0;
        border-radius: .85rem 0 0 .85rem;
        box-shadow: 0 .35rem 1rem rgba(0, 0, 0, .2);
        transform: translateY(-50%);
        transition: width .25s ease, background-color .25s ease, box-shadow .25s ease;
    }

    .test-lab-toggle:hover,
    .test-lab-toggle:focus-visible {
        width: 7rem;
        background-color: #ffca2c;
        box-shadow: 0 .5rem 1.25rem rgba(0, 0, 0, .28);
    }

    .test-lab-toggle__icon {
        flex: 0 0 auto;
        font-size: 1.15rem;
        line-height: 1;
    }

    .test-lab-toggle__label {
        max-width: 0;
        margin-left: 0;
        overflow: hidden;
        font-size: .875rem;
        font-weight: 600;
        opacity: 0;
        transition: max-width .25s ease, margin-left .25s ease, opacity .15s ease;
    }

    .test-lab-toggle:hover .test-lab-toggle__label,
    .test-lab-toggle:focus-visible .test-lab-toggle__label {
        max-width: 5rem;
        margin-left: .45rem;
        opacity: 1;
    }
</style>

<button
    class="btn btn-warning test-lab-toggle"
    type="button"
    data-bs-toggle="offcanvas"
    data-bs-target="#testLabPanel"
    aria-controls="testLabPanel"
    aria-label="Open test lab"
>
    <span class="test-lab-toggle__icon" aria-hidden="true">⚗</span>
    <span class="test-lab-toggle__label">Test lab</span>
</button>

<aside
    class="offcanvas offcanvas-end"
    tabindex="-1"
    id="testLabPanel"
    data-bs-backdrop="false"
    data-bs-scroll="true"
    aria-labelledby="testLabPanelTitle"
>
    <div class="offcanvas-header">
        <h2 class="offcanvas-title h5" id="testLabPanelTitle">Test lab</h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Hide test lab"></button>
    </div>
    <div class="offcanvas-body">
        <p class="text-body-secondary">Development tools for testing the application without stale cache.</p>

        <div class="border rounded p-3">
            <h3 class="h6">Application cache</h3>
            <p class="small text-body-secondary">Clear cached menu and page data, then reload the current page.</p>
            <form action="<?= basePath('test-lab/cache/clear'); ?>" method="post">
                <?= getCsrfField(); ?>
                <button type="submit" class="btn btn-danger w-100">Clear cache and reload</button>
            </form>
        </div>

        <?php if (isAuth()): ?>
            <?php if (!empty($createdCredentials)): ?>
            <div class="alert alert-success mt-3" role="status">
                <h3 class="h6">New user created</h3>
                <p class="small mb-2">Save these credentials. The password will not be shown again after this page is left.</p>
                <dl class="row small mb-0">
                    <dt class="col-4">Email</dt>
                    <dd class="col-8"><code><?= h($createdCredentials['email']); ?></code></dd>
                    <dt class="col-4">Password</dt>
                    <dd class="col-8"><code><?= h($createdCredentials['password']); ?></code></dd>
                </dl>
            </div>
            <?php endif; ?>

            <div class="border rounded p-3 mt-3">
            <h3 class="h6">Create test user</h3>
            <p class="small text-body-secondary">Leave any field empty to generate it automatically.</p>
            <form action="<?= basePath('dashboard/create-user'); ?>" method="post">
                <?= getCsrfField(); ?>

                <div class="mb-3">
                    <label for="test-user-name" class="form-label">Name</label>
                    <input name="name" id="test-user-name" type="text" class="form-control <?= isset($testUserErrors['name']) ? 'is-invalid' : ''; ?>" value="<?= h($testUserData['name'] ?? ''); ?>" autocomplete="name">
                    <?php if (isset($testUserErrors['name'])): ?>
                        <div class="invalid-feedback d-block"><?= h(implode(', ', $testUserErrors['name'])); ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="test-user-email" class="form-label">Email</label>
                    <input name="email" id="test-user-email" type="email" class="form-control <?= isset($testUserErrors['email']) ? 'is-invalid' : ''; ?>" value="<?= h($testUserData['email'] ?? ''); ?>" autocomplete="email">
                    <?php if (isset($testUserErrors['email'])): ?>
                        <div class="invalid-feedback d-block"><?= h(implode(', ', $testUserErrors['email'])); ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="test-user-password" class="form-label">Password</label>
                    <input name="password" id="test-user-password" type="text" class="form-control <?= isset($testUserErrors['password']) ? 'is-invalid' : ''; ?>" autocomplete="new-password">
                    <?php if (isset($testUserErrors['password'])): ?>
                        <div class="invalid-feedback d-block"><?= h(implode(', ', $testUserErrors['password'])); ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary w-100">Create user</button>
            </form>
            </div>

            <?php if (\PHPFramework\Config::$testLabPhpExecutionEnabled): ?>
            <div class="border rounded p-3 mt-3">
                <h3 class="h6">Execute PHP</h3>
                <p class="small text-danger">Use only for local development. Code runs with full application privileges.</p>
                <form action="<?= basePath('test-lab/execute-php'); ?>" method="post">
                    <?= getCsrfField(); ?>
                    <label for="test-lab-php-code" class="visually-hidden">PHP code</label>
                    <textarea name="php_code" id="test-lab-php-code" class="form-control font-monospace" rows="7" spellcheck="false" placeholder="echo app()::class;\nreturn 2 + 2;"></textarea>
                    <div class="form-text">Enter PHP without <code>&lt;?php</code> tags. Output from <code>echo</code> and the return value are captured.</div>
                    <button type="submit" class="btn btn-warning w-100 mt-3">Execute PHP</button>
                </form>
            </div>

            <?php if (!empty($phpExecution)): ?>
                <div class="alert alert-<?= $phpExecution['status'] === 'success' ? 'success' : 'danger'; ?> mt-3" role="status">
                    <h3 class="h6">PHP execution <?= $phpExecution['status'] === 'success' ? 'result' : 'error'; ?></h3>
                    <?php if (!empty($phpExecution['error'])): ?>
                        <pre class="small mb-2"><code><?= h($phpExecution['error']); ?></code></pre>
                    <?php endif; ?>
                    <?php if ($phpExecution['output'] !== ''): ?>
                        <strong class="small">Output</strong>
                        <pre class="small mb-2"><code><?= h($phpExecution['output']); ?></code></pre>
                    <?php endif; ?>
                    <strong class="small">Return value</strong>
                    <pre class="small mb-0"><code><?= h($phpExecution['return']); ?></code></pre>
                </div>
            <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-secondary mt-3 mb-0" role="status">
                    PHP execution is disabled. Set <code>TEST_LAB_PHP_EXECUTION_ENABLED=1</code> in <code>.env</code> to enable it.
                </div>
            <?php endif; ?>

            <?php if (\PHPFramework\Config::$testLabSqlConsoleEnabled): ?>
            <div class="border rounded p-3 mt-3">
                <h3 class="h6">SQL console</h3>
                <p class="small text-danger">Use only for local development. SQL runs with the application's database privileges.</p>
                <form id="test-lab-sql-form" action="<?= basePath('test-lab/execute-sql'); ?>" method="post">
                    <?= getCsrfField(); ?>
                    <label for="test-lab-sql" class="visually-hidden">SQL statement</label>
                    <textarea name="sql" id="test-lab-sql" class="form-control font-monospace" rows="7" spellcheck="false" placeholder="SELECT * FROM users LIMIT 10;"></textarea>
                    <div class="form-text">Execute one SQL statement. Query results and affected rows are shown below.</div>
                    <button type="submit" class="btn btn-warning w-100 mt-3">Execute SQL</button>
                </form>
            </div>

            <div id="test-lab-sql-result">
            <?php if (!empty($sqlConsole)): ?>
                <div class="alert alert-<?= $sqlConsole['status'] === 'success' ? 'success' : 'danger'; ?> mt-3" role="status">
                    <h3 class="h6">SQL execution <?= $sqlConsole['status'] === 'success' ? 'result' : 'error'; ?></h3>
                    <?php if (!empty($sqlConsole['error'])): ?>
                        <pre class="small mb-2"><code><?= h($sqlConsole['error']); ?></code></pre>
                    <?php endif; ?>
                    <?php if ($sqlConsole['status'] === 'success' && !empty($sqlConsole['columns'])): ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <?php foreach ($sqlConsole['columns'] as $column): ?>
                                            <th scope="col"><?= h($column); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sqlConsole['rows'] as $row): ?>
                                        <tr>
                                            <?php foreach ($sqlConsole['columns'] as $column): ?>
                                                <td><code><?= h((string) ($row[$column] ?? '')); ?></code></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (empty($sqlConsole['rows'])): ?>
                            <p class="small mb-0">The query returned no rows.</p>
                        <?php endif; ?>
                    <?php elseif ($sqlConsole['status'] === 'success'): ?>
                        <p class="small mb-0">Affected rows: <?= h((string) ($sqlConsole['affected_rows'] ?? 0)); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            </div>
            <?php else: ?>
                <div class="alert alert-secondary mt-3 mb-0" role="status">
                    SQL console is disabled. Set <code>TEST_LAB_SQL_CONSOLE_ENABLED=1</code> in <code>.env</code> to enable it.
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-info mt-3" role="status">
                Sign in to use the test-user generator.
                <a href="<?= basePath('login'); ?>" class="alert-link">Log in</a>
                or
                <a href="<?= basePath('register'); ?>" class="alert-link">create an account</a>.
            </div>
        <?php endif; ?>
    </div>
</aside>

<?php
session()->remove('test_user_errors');
session()->remove('test_user_data');
?>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const testLabPanel = document.getElementById('testLabPanel');
        const testLabToggle = document.querySelector('.test-lab-toggle');
        const sqlForm = document.getElementById('test-lab-sql-form');
        const sqlResult = document.getElementById('test-lab-sql-result');

        if (!testLabPanel || !testLabToggle) {
            return;
        }

        document.addEventListener('click', (event) => {
            if (!testLabPanel.classList.contains('show')) {
                return;
            }

            if (!testLabPanel.contains(event.target) && !testLabToggle.contains(event.target)) {
                bootstrap.Offcanvas.getOrCreateInstance(testLabPanel).hide();
            }
        });

        if (sqlForm && sqlResult) {
            sqlForm.addEventListener('submit', async (event) => {
                event.preventDefault();

                const submitButton = sqlForm.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                }

                sqlResult.innerHTML = '<div class="alert alert-secondary mt-3" role="status">Executing SQL...</div>';

                try {
                    const response = await fetch(sqlForm.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: new FormData(sqlForm),
                        credentials: 'same-origin',
                    });
                    const result = await response.json();
                    renderSqlResult(result);
                } catch (error) {
                    renderSqlResult({
                        status: 'error',
                        error: 'The SQL request could not be completed.',
                        columns: [],
                        rows: [],
                        affected_rows: null,
                    });
                } finally {
                    if (submitButton) {
                        submitButton.disabled = false;
                    }
                }
            });
        }

        function renderSqlResult(result) {
            sqlResult.replaceChildren();
            const alert = document.createElement('div');
            alert.className = `alert alert-${result.status === 'success' ? 'success' : 'danger'} mt-3`;
            alert.setAttribute('role', 'status');

            const heading = document.createElement('h3');
            heading.className = 'h6';
            heading.textContent = `SQL execution ${result.status === 'success' ? 'result' : 'error'}`;
            alert.appendChild(heading);

            if (result.error) {
                const error = document.createElement('pre');
                error.className = 'small mb-2';
                const errorCode = document.createElement('code');
                errorCode.textContent = result.error;
                error.appendChild(errorCode);
                alert.appendChild(error);
            }

            if (result.status === 'success' && Array.isArray(result.columns) && result.columns.length > 0) {
                const wrapper = document.createElement('div');
                wrapper.className = 'table-responsive';
                const table = document.createElement('table');
                table.className = 'table table-sm align-middle mb-0';
                const head = document.createElement('thead');
                const headerRow = document.createElement('tr');

                result.columns.forEach((column) => {
                    const cell = document.createElement('th');
                    cell.scope = 'col';
                    cell.textContent = column;
                    headerRow.appendChild(cell);
                });

                head.appendChild(headerRow);
                table.appendChild(head);

                const body = document.createElement('tbody');
                (result.rows || []).forEach((row) => {
                    const tableRow = document.createElement('tr');
                    result.columns.forEach((column) => {
                        const cell = document.createElement('td');
                        const value = document.createElement('code');
                        value.textContent = row[column] ?? '';
                        cell.appendChild(value);
                        tableRow.appendChild(cell);
                    });
                    body.appendChild(tableRow);
                });
                table.appendChild(body);
                wrapper.appendChild(table);
                alert.appendChild(wrapper);

                if (!result.rows || result.rows.length === 0) {
                    const empty = document.createElement('p');
                    empty.className = 'small mb-0';
                    empty.textContent = 'The query returned no rows.';
                    alert.appendChild(empty);
                }
            } else if (result.status === 'success') {
                const affected = document.createElement('p');
                affected.className = 'small mb-0';
                affected.textContent = `Affected rows: ${result.affected_rows ?? 0}`;
                alert.appendChild(affected);
            }

            sqlResult.appendChild(alert);
        }
    });
</script>
