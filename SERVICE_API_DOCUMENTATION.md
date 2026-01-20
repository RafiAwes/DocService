## Service API Endpoints - Refactored

### CREATE OPERATIONS

#### 1. Create Base Service
**POST** `/api/admin/service/create-base`

```json
{
    "category_id": 1,
    "is_south_african": 1,
    "title": "Business Registration",
    "subtitle": "Complete setup for your LLC",
    "order_type": "checkout",
    "type": "Checkout",
    "price": 500,
    "description": "We handle the registration process",
    "short_description": "Short description",
    "image": "file_upload"
}
```

Response: Returns service ID with base data

---

#### 2. Add Included Services
**POST** `/api/admin/service/{service_id}/add-included-services`

```json
{
    "included_services": [
        {
            "service_type": "Government Fee",
            "included_details": "Standard state filing fee",
            "price": 300
        },
        {
            "service_type": "Registered Agent",
            "included_details": "1 Year of agent service",
            "price": 150
        }
    ]
}
```

---

#### 3. Add Processing Times
**POST** `/api/admin/service/{service_id}/add-processing-times`

```json
{
    "processing_times": [
        {
            "details": "Standard Processing",
            "time": "15 - 20 working days"
        },
        {
            "details": "Expedited",
            "time": "5 - 7 working days"
        }
    ]
}
```

---

#### 4. Add Questions
**POST** `/api/admin/service/{service_id}/add-questions`

```json
{
    "questions": [
        {
            "name": "What is your preferred business name?",
            "type": "Textbox"
        },
        {
            "name": "Select your business entity type",
            "type": "Drop down",
            "options": ["LLC", "Corporation", "Non-Profit"]
        }
    ]
}
```

---

#### 5. Add Required Documents
**POST** `/api/admin/service/{service_id}/add-required-documents`

```json
{
    "required_documents": [
        {
            "title": "Copy of ID / Passport"
        },
        {
            "title": "Proof of Address"
        }
    ]
}
```

---

#### 6. Add How It Works
**POST** `/api/admin/service/{service_id}/add-how-it-works`

```json
{
    "how_it_works": [
        "Step 1: Fill out the form",
        "Step 2: Submit required documents",
        "Step 3: We process your request"
    ]
}
```

---

### UPDATE OPERATIONS

#### 7. Update Included Services
**PUT** `/api/admin/service/{service_id}/update-included-services`

```json
{
    "included_services": [
        {
            "id": 28,
            "service_type": "Government Fee",
            "included_details": "Updated details",
            "price": 350
        },
        {
            "service_type": "New Service",
            "included_details": "New service details",
            "price": 200
        }
    ]
}
```

---

#### 8. Update Processing Times
**PUT** `/api/admin/service/{service_id}/update-processing-times`

```json
{
    "processing_times": [
        {
            "id": 13,
            "details": "Standard Processing",
            "time": "20 - 25 working days"
        },
        {
            "details": "New Option",
            "time": "10 working days"
        }
    ]
}
```

---

#### 9. Update Questions
**PUT** `/api/admin/service/{service_id}/update-questions`

```json
{
    "questions": [
        {
            "id": 23,
            "name": "Updated question?",
            "type": "Textbox"
        },
        {
            "name": "New question?",
            "type": "Drop down",
            "options": ["Option A", "Option B"]
        }
    ]
}
```

---

#### 10. Update Required Documents
**PUT** `/api/admin/service/{service_id}/update-required-documents`

```json
{
    "required_documents": [
        {
            "id": 23,
            "title": "Updated ID requirement"
        },
        {
            "title": "New document requirement"
        }
    ]
}
```

---

#### 11. Update How It Works
**PUT** `/api/admin/service/{service_id}/update-how-it-works`

```json
{
    "how_it_works": [
        "Updated Step 1",
        "Updated Step 2",
        "New Step 3"
    ]
}
```

---

### LEGACY ENDPOINTS (Still Supported)

#### Create Service (Full)
**POST** `/api/admin/create/service`

Creates service with all relations in one call.

#### Update Service (Full)
**PUT** `/api/admin/update/service/{service_id}`

Updates service and all relations in one call.

#### Delete Service
**DELETE** `/api/admin/delete/service/{service_id}`

Deletes the entire service.

---

### Usage Flow

**Recommended flow for new services:**

1. Create base service: `POST /api/admin/service/create-base`
2. Add included services: `POST /api/admin/service/{id}/add-included-services`
3. Add processing times: `POST /api/admin/service/{id}/add-processing-times`
4. Add questions: `POST /api/admin/service/{id}/add-questions`
5. Add documents: `POST /api/admin/service/{id}/add-required-documents`
6. Add how it works: `POST /api/admin/service/{id}/add-how-it-works`

**For updates:**
- Update only what you need
- Empty arrays will delete all items of that type
- Send IDs to update existing items
- Send new items without IDs to add them
