# Project linting rules

These rules apply to every PHP file in the project. The same principles should be used in other languages when the language supports an equivalent style.

## Comments

- Do not add comments to source code.

## Blank lines and statement grouping

- Keep related statements together.
- Separate a statement from the code before and after it when it starts a new logical operation.
- Separate function or method calls that perform an action from variable assignments.
- A construction such as `if`, `else`, `for`, `foreach`, `while`, `switch`, `try`, `catch`, or `finally` must be separated from surrounding statements.
- Add a blank line before and after a construction when there is surrounding code on that side.
- If a construction is the first statement inside any block, including a function, method, loop, or other construction, do not add a blank line between the opening brace and the construction.
- The first statement inside a construction must immediately follow its opening brace without a blank line.
- Do not add a blank line after a construction when it is the final statement in the function or method.
- A `return` statement must be separated from the preceding logical operation. If it is the first statement in a function or method, it may follow the opening brace immediately.
- Separate a multiline statement from surrounding statements on both sides when code exists on those sides.
- A multiline statement means a statement that spans more than two lines, including multiline function calls, arrays, conditions, and assignments.

## Function and control-structure formatting

Use same-line opening braces:

```php
if ($condition) {
    doSomething();
} else {
    doSomethingElse();
}

function processValue($value) {
    return $value;
}
```

When a construction is the first statement in a function, keep it directly after the function opening:

```php
public function login() {
    if (isAuth()) {
        response()->redirect('/dashboard');
    }

    return view('user/login', ['title' => 'Login page']);
}
```

When a construction follows another statement, separate it from that statement:

```php
$code = trim((string) request()->post('php_code'));

if ($code === '') {
    response()->redirect('/');
}
```

Do not put a blank line between a construction's opening brace and its first statement:

```php
if ($condition) {
    executeAction();
}
```

## Naming

- Use conventional PHP naming.
- Use `camelCase` for variables, parameters, methods, and functions.
- Use `PascalCase` for classes, interfaces, traits, and enums.
- Use `UPPER_SNAKE_CASE` for constants.
- Use descriptive names instead of abbreviations or unclear single-letter names, except for conventional short-lived loop variables.

## Line length and readability

- Keep statements readable and avoid unnecessarily long lines.
- Break long function calls, arrays, conditions, and assignments into multiple lines when needed.
- Keep multiline expressions consistently indented.
- Do not leave trailing whitespace.
- Keep files free of syntax errors and formatting errors.

## Namespaces

- Do not check for linting rules directories such as:
    - /vendor
    - /assets
    - /lang
    - /tmp
