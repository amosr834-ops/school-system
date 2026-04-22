import React from "react";
function Academic() {
return (
            <>

            <div className="grades-card">
                    <h2>Grade Seven Academic Results</h2>
                    
                    <table border="1">
                        <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Marks</th>
                            <th>Percentage</th>
                            <th>Performance Level</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>English</td>
                            <td>33/40</td>
                            <td>83%</td>
                            <td>Exceeding Expectation</td>
                        </tr>
                        <tr>
                            <td>Kiswahili</td>
                            <td>29/40</td>
                            <td>73%</td>
                            <td>Meeting Expectation</td>
                        </tr>
                        <tr>
                            <td>Mathematics</td>
                            <td>16/25</td>
                            <td>64%</td>
                            <td>Meeting Expectation</td>
                        </tr>
                        <tr>
                            <td>Science Tech</td>
                            <td>24/25</td>
                            <td>96%</td>
                            <td>Exceeding Expectation</td>
                        </tr>
                        <tr>
                            <td>CRE</td>
                            <td>28/30</td>
                            <td>93%</td>
                            <td>Exceeding Expectation</td>
                        </tr>
                        <tr>
                            <td>Creative Arts</td>
                            <td>28/35</td>
                            <td>80%</td>
                            <td>Exceeding Expectation</td>
                        </tr>
                        </tbody>
                    </table>

                    <h3>Summary</h3>
                    <div className="summary">
                        <div className="column">
                            <p><strong>Total Marks:</strong> <ins>158/195</ins></p>
                            <p><strong>Average Marks:</strong> <ins>85% (Exceeding Expectation)</ins></p>
                        </div>
                        <div className="column">
                            <p><strong>Position Rank:</strong> <ins>2/128</ins></p>
                            <p><strong>Teacher’s Comment:</strong> <ins>Very Good</ins></p>
                        </div>
                     </div>
             </div>

        </>
    );
}
export default Academic