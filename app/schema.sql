PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS Address (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    street TEXT,
    streetNumber TEXT,
    postalCode TEXT,
    city TEXT,
    country TEXT
);

CREATE TABLE IF NOT EXISTS Person (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    firstName TEXT NOT NULL,
    lastName TEXT NOT NULL,
    nickname TEXT,
    birthday TEXT,
    gender TEXT,
    jobTitle TEXT,
    nationality TEXT,
    email TEXT NOT NULL UNIQUE,
    username TEXT,
    password TEXT NOT NULL,
    mobile TEXT,
    fsvl TEXT,
    address INTEGER,
    image TEXT,
    language TEXT DEFAULT 'fr',
    iban TEXT,
    role TEXT DEFAULT 'M',
    partnerName TEXT,
    createdAt TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(address) REFERENCES Address(id)
);

CREATE TABLE IF NOT EXISTS Member (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    person INTEGER NOT NULL UNIQUE,
    type TEXT NOT NULL DEFAULT 'actif',
    canBook INTEGER NOT NULL DEFAULT 1,
    partnerCode TEXT,
    FOREIGN KEY(person) REFERENCES Person(id)
);

CREATE TABLE IF NOT EXISTS License (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    person INTEGER NOT NULL,
    label TEXT NOT NULL,
    number TEXT,
    validUntil TEXT,
    FOREIGN KEY(person) REFERENCES Person(id)
);

CREATE TABLE IF NOT EXISTS Driver (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    person INTEGER NOT NULL UNIQUE,
    status INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY(person) REFERENCES Person(id)
);

CREATE TABLE IF NOT EXISTS Manager (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    person INTEGER NOT NULL UNIQUE,
    rights INTEGER NOT NULL DEFAULT 1,
    FOREIGN KEY(person) REFERENCES Person(id)
);

CREATE TABLE IF NOT EXISTS Vehicule (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    registration TEXT,
    label TEXT,
    seats INTEGER NOT NULL DEFAULT 8,
    status INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS Rule (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    label TEXT NOT NULL,
    para TEXT,
    chronology INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS BookingRule (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    rule INTEGER,
    sequence INTEGER DEFAULT 0,
    FOREIGN KEY(rule) REFERENCES Rule(id)
);

CREATE TABLE IF NOT EXISTS Journey (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    bookingRule INTEGER,
    driver INTEGER,
    vehicule INTEGER,
    Label TEXT NOT NULL,
    kind TEXT DEFAULT 'club',
    dateFrom TEXT NOT NULL,
    dateTo TEXT,
    timeStart TEXT,
    timeEnd TEXT,
    started INTEGER NOT NULL DEFAULT 0,
    ended INTEGER NOT NULL DEFAULT 0,
    createdBy INTEGER,
    notes TEXT,
    FOREIGN KEY(bookingRule) REFERENCES BookingRule(id),
    FOREIGN KEY(driver) REFERENCES Driver(id),
    FOREIGN KEY(vehicule) REFERENCES Vehicule(id),
    FOREIGN KEY(createdBy) REFERENCES Person(id)
);

CREATE TABLE IF NOT EXISTS Ticket (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    person INTEGER NOT NULL,
    quantity INTEGER NOT NULL,
    price NUMERIC NOT NULL,
    date TEXT NOT NULL,
    used INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY(person) REFERENCES Person(id)
);

CREATE TABLE IF NOT EXISTS YearFee (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    year INTEGER NOT NULL,
    type TEXT NOT NULL,
    price NUMERIC NOT NULL,
    UNIQUE(year, type)
);

CREATE TABLE IF NOT EXISTS MemberYearFee (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    member INTEGER NOT NULL,
    yearFee INTEGER NOT NULL,
    date TEXT,
    status TEXT NOT NULL DEFAULT 'pending',
    amount NUMERIC NOT NULL DEFAULT 0,
    paymentMethod TEXT,
    FOREIGN KEY(member) REFERENCES Member(id),
    FOREIGN KEY(yearFee) REFERENCES YearFee(id),
    UNIQUE(member, yearFee)
);

CREATE TABLE IF NOT EXISTS Payment (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    person INTEGER NOT NULL,
    memberYearFee INTEGER,
    kind TEXT NOT NULL,
    description TEXT NOT NULL,
    quantity INTEGER,
    unitAmount NUMERIC,
    amount NUMERIC NOT NULL,
    currency TEXT NOT NULL DEFAULT 'chf',
    provider TEXT NOT NULL DEFAULT 'stripe',
    status TEXT NOT NULL DEFAULT 'pending',
    providerSessionId TEXT,
    providerPaymentIntentId TEXT,
    providerChargeId TEXT,
    providerReceiptUrl TEXT,
    providerPayload TEXT,
    fulfilledAt TEXT,
    paidAt TEXT,
    createdAt TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(person) REFERENCES Person(id),
    FOREIGN KEY(memberYearFee) REFERENCES MemberYearFee(id)
);

CREATE TABLE IF NOT EXISTS Booking (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    journey INTEGER NOT NULL,
    member INTEGER NOT NULL,
    ticket INTEGER,
    disable INTEGER NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'booked',
    guestName TEXT,
    validatedAt TEXT,
    qrCode TEXT,
    createdAt TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(journey) REFERENCES Journey(id),
    FOREIGN KEY(member) REFERENCES Member(id),
    FOREIGN KEY(ticket) REFERENCES Ticket(id)
);

CREATE TABLE IF NOT EXISTS Content (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    timestamp TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    msgFrom INTEGER,
    label TEXT NOT NULL,
    body TEXT,
    FOREIGN KEY(msgFrom) REFERENCES Person(id)
);

CREATE TABLE IF NOT EXISTS Message (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    content INTEGER NOT NULL,
    msgFrom INTEGER,
    msgTo INTEGER,
    validSince TEXT,
    validUntil TEXT,
    sent TEXT,
    received TEXT,
    checked TEXT,
    status TEXT NOT NULL DEFAULT 'draft',
    recipients TEXT,
    audience TEXT DEFAULT 'all',
    extraRecipients TEXT,
    smtpError TEXT,
    recipientEmails TEXT,
    updatedAt TEXT,
    FOREIGN KEY(content) REFERENCES Content(id),
    FOREIGN KEY(msgFrom) REFERENCES Person(id)
);

CREATE TABLE IF NOT EXISTS MessageAttachment (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    message INTEGER NOT NULL,
    originalName TEXT NOT NULL,
    storedName TEXT NOT NULL,
    mimeType TEXT,
    size INTEGER NOT NULL DEFAULT 0,
    createdAt TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(message) REFERENCES Message(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Settings (
    key TEXT PRIMARY KEY,
    value TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS Journal (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    timestamp TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    person INTEGER,
    label TEXT NOT NULL,
    disableTimestamp TEXT,
    disabledBy INTEGER,
    FOREIGN KEY(person) REFERENCES Person(id),
    FOREIGN KEY(disabledBy) REFERENCES Person(id)
);
