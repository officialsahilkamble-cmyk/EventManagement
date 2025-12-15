-- Fix category icons to include 'fas' prefix
UPDATE categories
SET icon = 'fas fa-palette'
WHERE slug = 'art-design';
UPDATE categories
SET icon = 'fas fa-tshirt'
WHERE slug = 'fashion';
UPDATE categories
SET icon = 'fas fa-utensils'
WHERE slug = 'food-culinary';
UPDATE categories
SET icon = 'fas fa-music'
WHERE slug = 'music';
UPDATE categories
SET icon = 'fas fa-futbol'
WHERE slug = 'sports';
UPDATE categories
SET icon = 'fas fa-laptop-code'
WHERE slug = 'technology';
-- Add more default categories if needed
INSERT IGNORE INTO categories (name, slug, icon)
VALUES ('Business', 'business', 'fas fa-briefcase'),
    (
        'Education',
        'education',
        'fas fa-graduation-cap'
    ),
    (
        'Health & Wellness',
        'health-wellness',
        'fas fa-heartbeat'
    ),
    ('Entertainment', 'entertainment', 'fas fa-film');