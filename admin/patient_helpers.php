<?php
// Shared helpers for the admin patient pages.
// Reads the real column names from your `users` and `patients` tables,
// so the queries never ask for a column that doesn't exist.

function table_columns(mysqli $conn, string $table): array {
	$cols = [];
	try {
		$res = $conn->query("SHOW COLUMNS FROM `$table`");
		while ($r = $res->fetch_assoc()) {
			$cols[] = $r["Field"];
		}
	} catch (mysqli_sql_exception $e) {
		// table doesn't exist -> no columns
	}
	return $cols;
}

function first_col(array $cols, array $candidates): ?string {
	foreach ($candidates as $c) {
		if (in_array($c, $cols, true)) {
			return $c;
		}
	}
	return null;
}

// Possible names for each field (first match wins)
const PATIENT_FIELDS = [
	"fullname"   => ["fullname", "full_name", "name"],
	"ic_number"  => ["ic_number", "ic_no", "ic", "nric"],
	"email"      => ["email"],
	"phone"      => ["phone", "phone_number", "phone_no", "contact", "contact_number"],
	"created_at" => ["created_at", "registered_at", "date_created", "reg_date"],
];

function patient_schema(mysqli $conn): array {
	$u = table_columns($conn, "users");
	$p = table_columns($conn, "patients");

	// Column in `patients` that points to users.id
	$link = first_col($p, ["user_id", "users_id", "patient_id"]);

	$map = [];
	foreach (PATIENT_FIELDS as $key => $cands) {
		$map[$key] = [
			"u" => first_col($u, $cands),
			"p" => $link ? first_col($p, $cands) : null,
		];
	}

	return [
		"link"     => $link,
		"map"      => $map,
		"has_role" => in_array("role", $u, true),
		"username" => first_col($u, ["username", "email"]),
		"password" => first_col($u, ["password", "pass", "user_password"]),
	];
}

function patient_select_sql(array $s, string $extraWhere = ""): string {
	$parts = ["u.id AS id"];
	$parts[] = $s["username"] ? "u.`{$s['username']}` AS username" : "NULL AS username";

	foreach ($s["map"] as $key => $m) {
		$pieces = [];
		if ($m["p"]) $pieces[] = "p.`{$m['p']}`";
		if ($m["u"]) $pieces[] = "u.`{$m['u']}`";
		if ($key === "fullname" && $s["username"]) $pieces[] = "u.`{$s['username']}`";
		$parts[] = ($pieces ? "COALESCE(" . implode(", ", $pieces) . ")" : "NULL") . " AS $key";
	}

	$sql = "SELECT " . implode(",\n       ", $parts) . "\nFROM users u";
	if ($s["link"]) {
		$sql .= "\nLEFT JOIN patients p ON p.`{$s['link']}` = u.id";
	}

	$where = [];
	if ($s["has_role"]) $where[] = "u.role = 'customer'";
	if ($extraWhere !== "") $where[] = $extraWhere;
	if ($where) $sql .= "\nWHERE " . implode(" AND ", $where);

	return $sql . "\nORDER BY created_at DESC, u.id DESC";
}

function load_patient(mysqli $conn, array $s, int $id): ?array {
	$stmt = $conn->prepare(patient_select_sql($s, "u.id = ?"));
	$stmt->bind_param("i", $id);
	$stmt->execute();
	$row = $stmt->get_result()->fetch_assoc();
	return $row ?: null;
}

function update_table(mysqli $conn, string $table, string $keyCol, int $id, array $values): void {
	if (!$values) return;
	$set = implode(", ", array_map(fn($c) => "`$c` = ?", array_keys($values)));
	$stmt = $conn->prepare("UPDATE `$table` SET $set WHERE `$keyCol` = ?");
	$params = array_values($values);
	$params[] = $id;
	$stmt->bind_param(str_repeat("s", count($values)) . "i", ...$params);
	$stmt->execute();
}

// Saves each field to every table that has that column (keeps both in sync)
function save_patient_fields(mysqli $conn, array $s, int $id, array $data): void {
	$u = [];
	$p = [];
	foreach ($data as $key => $val) {
		$m = $s["map"][$key] ?? null;
		if (!$m) continue;
		if ($m["u"]) $u[$m["u"]] = $val;
		if ($m["p"]) $p[$m["p"]] = $val;
	}
	update_table($conn, "users", "id", $id, $u);
	if ($s["link"]) {
		update_table($conn, "patients", $s["link"], $id, $p);
	}
}

// Stores the new password in the same format as the old one,
// so your existing login.php keeps working.
function hash_like_existing(string $new, ?string $old): string {
	$old = (string) $old;
	if ($old === "" || !empty(password_get_info($old)["algo"])) {
		return password_hash($new, PASSWORD_DEFAULT); // bcrypt (recommended)
	}
	if (preg_match('/^[a-f0-9]{32}$/i', $old)) return md5($new);
	if (preg_match('/^[a-f0-9]{40}$/i', $old)) return sha1($new);
	return $new; // plain text (not recommended)
}