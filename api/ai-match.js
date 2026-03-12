export default async function handler(req, res) {
    if (req.method !== "POST") {
        return res.status(405).json({ error: "Method not allowed" });
    }

    try {
        const { jobDescription } = req.body || {};

        if (!jobDescription || !jobDescription.trim()) {
            return res.status(400).json({ error: "Missing jobDescription" });
        }

        const portfolioContext = `
You are an assistant that analyzes how well this candidate fits a role.

Candidate name: Tom Huynh

Profile summary:
- Tech-focused candidate with hands-on experience in web development, technical troubleshooting, customer support, and portfolio projects.
- Interested in Software Engineering, Web Development, and DevOps-oriented roles.

Skills:
- JavaScript
- HTML
- CSS
- React
- Python
- Node.js
- PHP
- MySQL
- Git / GitHub
- API integration
- Responsive UI
- Problem solving
- Troubleshooting
- Communication
- Customer support
- Lua / FiveM scripting
- Deployment fundamentals

Projects:
1. Australia Sales Dashboard
   - Focus: Python, data handling, dashboard thinking, presentation of insights

2. GTA STREET Architecture
   - Focus: Lua, MySQL, backend logic, system architecture, reliability, active-user environment

3. TomWings / SkyBooking
   - Focus: HTML, CSS, JavaScript, responsive design, polished frontend UI

Work experience:
- Smartphone Specialist at Happytel
  - Technical troubleshooting
  - Device diagnostics
  - Customer-facing technical communication
  - Problem solving under time pressure

Task:
Given the hiring brief, return strict JSON with this shape:
{
  "matchScore": number,
  "matchedSkills": string[],
  "recommendedProjects": [
    {
      "name": string,
      "reason": string
    }
  ],
  "summary": string,
  "strengths": string[],
  "missingSkills": string[]
}

Rules:
- Be realistic, not overly flattering.
- Keep summary concise, recruiter-friendly, and specific.
- Match score must be between 0 and 100.
- Return JSON only.
`;

        const response = await fetch("https://api.openai.com/v1/responses", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Authorization: `Bearer ${process.env.OPENAI_API_KEY}`,
            },
            body: JSON.stringify({
                model: process.env.OPENAI_MODEL || "gpt-5-mini",
                input: [
                    {
                        role: "system",
                        content: portfolioContext,
                    },
                    {
                        role: "user",
                        content: `Hiring brief:\n${jobDescription}`,
                    },
                ],
            }),
        });

        if (!response.ok) {
            const text = await response.text();
            return res.status(response.status).json({
                error: "OpenAI request failed",
                details: text,
            });
        }

        const data = await response.json();

        const rawText =
            data.output_text ||
            data.output?.map(item => item?.content?.map(c => c?.text).join("")).join("") ||
            "";

        let parsed;
        try {
            parsed = JSON.parse(rawText);
        } catch {
            return res.status(500).json({
                error: "Model did not return valid JSON",
                raw: rawText,
            });
        }

        return res.status(200).json(parsed);
    } catch (error) {
        return res.status(500).json({
            error: "Server error",
            details: error.message,
        });
    }
}