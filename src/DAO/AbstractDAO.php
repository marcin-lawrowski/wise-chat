<?php

namespace Kainex\WiseChat\DAO;

/**
 * Abstract DAO
 *
 * @author Kainex <contact@kainex.pl>
 */
abstract class AbstractDAO {

	/**
	 * @param array $tableRow
	 * @return int ID of the object
	 */
	protected function persist(array $tableRow): int {
		global $wpdb;

		if (isset($tableRow['id'])) {
			$id = $tableRow['id'];
			unset($tableRow['id']);
			$wpdb->update($this->getTableName(), $tableRow, array('id' => $id), '%s', '%d');

			return $id;
		} else {
			$wpdb->insert($this->getTableName(), $tableRow);

			return $wpdb->insert_id;
		}
	}

	/**
     * @param integer $id
     */
    public function deleteById(int $id) {
        global $wpdb;

        $wpdb->query($wpdb->prepare("DELETE FROM %i WHERE `id` = %d;", $this->getTableName(), $id));
    }

	/**
     * @param array $conditions
     */
    protected function deleteBy(array $conditions) {
        global $wpdb;

        $wpdb->query($wpdb->prepare("DELETE FROM %i WHERE ".$this->prepareConditions($conditions), $this->getTableName()));
    }

	/**
	 * @param array $conditions
	 * @return object|null
	 */
	protected function getOneBy(array $conditions): ?object {
		global $wpdb;

		$sql = $wpdb->prepare('SELECT * FROM %i WHERE '.$this->prepareConditions($conditions).' LIMIT 1', $this->getTableName());
		$results = $wpdb->get_results($sql);
		if (is_array($results) && count($results) > 0) {
			return $results[0];
		}

		return null;
	}

	protected function getAllBy(array $conditions, ?array $sort = null, ?int $limit = null, ?int $offset = null, array $definition = []): array {
		global $wpdb;

		$conditions = $this->prepareConditions($conditions);

		$joins = [];
		if (isset($definition['join'])) {
			foreach ($definition['join'] as $join) {
				$joins[] = $wpdb->prepare('LEFT JOIN %i '.$join[1].' ON ('.implode(' AND ', $join[2]).')', $join[0]);
			}
		}
		$joinsSQL = implode(" ", $joins);
		$selector = $definition['select'] ?? '*';
		$sortSQL = $sort ? $wpdb->prepare(" ORDER BY %i ".$sort[1], $sort[0]) : '';
		$limitSQL = $limit ? $wpdb->prepare(" LIMIT %d ", $limit) : '';
		$offsetSQL = $offset ? $wpdb->prepare(" OFFSET %d ", $offset) : '';
		$conditionsSQL = $conditions ? ' WHERE '.$conditions : '';
		$sql = $wpdb->prepare('SELECT '.$selector.' FROM %i AS main '.$joinsSQL.$conditionsSQL.$sortSQL.$limitSQL.$offsetSQL, $this->getTableName());

		$results = $wpdb->get_results($sql);

		if (is_array($results)) {
			return $results;
		}

		return [];
	}

	abstract protected function getTableName(): string;
	abstract protected function populateData(\stdClass $rawRow): object;

	private function prepareConditions(array $conditions): string {
		global $wpdb;

		$preparedConditions = [];
		foreach ($conditions as $column => $value) {
			if (is_numeric($column)) {
				$column = array_shift($value);
			}

			if (is_array($value)) {
				$fieldValue = $value[0];
				$fieldValueType = $value[1];
				$operator = count($value) === 3 ? $value[2] : null;

				if (is_array($fieldValue)) {
					$que = implode(',', array_fill(0, count($fieldValue), $fieldValueType));
					$preparedConditions[] = $wpdb->prepare($this->escapeColumnName($column)." ".($operator ?? 'IN')." (".$que.")", $fieldValue);
				} else if ($operator) {
					if ($operator === 'search') {
						$operator = 'like';
						$fieldValue = '%'.$wpdb->esc_like($fieldValue).'%';
					}

					$preparedConditions[] = $wpdb->prepare($this->escapeColumnName($column)." ".$operator." " . $fieldValueType, $fieldValue);
				} else {
					$preparedConditions[] = $wpdb->prepare($this->escapeColumnName($column)." = " . $fieldValueType, $fieldValue);
				}
			} else {
				$preparedConditions[] = $wpdb->prepare($this->escapeColumnName($column)." = %s", $value);
			}
		}

		return implode(' AND ', $preparedConditions);
	}

	private function escapeColumnName(string $definition): string {
		global $wpdb;
		$split = explode('.', $definition);

		return implode('.', array_map(function(string $part) use ($wpdb) { return $wpdb->prepare("%i", $part); }, $split));
	}

}