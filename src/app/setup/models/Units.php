<?php

/**
 * Products class
 * - middleware
 */
class Units extends Model
{
    /** Check middleware information */
    public function __construct()
    {
        $this->table_name = "measurement_units";
        $this->table_columns = [
            'unit_id',
            'unit_name',
            'abbreviation',
            'category_id',
            'reference_unit'
        ];
    }

    // Get unit by country code
    public function get_units_by_country_code($country_code): array
    {
        $sql = "SELECT 
            c.country_id,
            c.country_code,
            c.country_name,
            c.region,
            mc.category_name,
            mu.unit_name,
            mu.abbreviation,
            mu.reference_unit
        FROM 
            countries c
        JOIN 
            country_measurement_units cmu ON c.country_id = cmu.country_id
        JOIN 
            measurement_units mu ON cmu.unit_id = mu.unit_id
        JOIN 
            measurement_categories mc ON mu.category_id = mc.category_id
        WHERE 
            c.country_code = :country_code
        ORDER BY 
            mc.category_name";

        $this->query($sql);
        $this->bind(':country_code', $country_code);
        $units = $this->resultset();
        return (array)$units;
    }
}

?>